<?php

namespace App\Filament\Imports;

use App\Models\Department;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\purchase_type_id;
use App\Models\User;
use App\Models\UserDepartment;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PurchaseRequisitionImporter extends Importer
{
    protected static ?string $model = PurchaseRequisition::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('number')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),

            ImportColumn::make('description')
                ->requiredMapping()
                ->rules(['required']),

            ImportColumn::make('status')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
        ];
    }

    public function resolveRecord(): ?PurchaseRequisition
    {
        try {
            // Cek apakah PR dengan nomor ini sudah ada
            $existingPR = PurchaseRequisition::where('number', $this->data['number'])->first();

            if ($existingPR) {
                Log::info('Purchase Requisition dengan nomor ini sudah ada:', ['number' => $this->data['number']]);

                // Update data jika sudah ada (kecuali number)
                $existingPR->update([
                    'description' => $this->data['description'] ?? $existingPR->description,
                    'status' => $this->data['status'] ?? $existingPR->status,
                    'approved_at' => $this->data['approved_at'] ?? $existingPR->approved_at,
                    'cancelled_at' => $this->data['cancelled_at'] ?? $existingPR->cancelled_at,
                ]);

                Log::info('Purchase Requisition berhasil diperbarui:', ['id' => $existingPR->id]);

                // **Pastikan items terkait dibuat atau diperbarui**
                if (!empty($this->data['items'])) {
                    $this->syncItemsWithPurchaseRequisition($existingPR, $this->data['items']);
                }

                return $existingPR; // Kembalikan PR yang diperbarui
            }

            // Jika tidak ada, buat Purchase Requisition baru
            $requestedByName = trim($this->data['requested_by']);
            $departmentName = trim($this->data['department_id']);
            $purchaseTypeId = (int) current(explode('.', trim($this->data['purchase_type_id'])));

            $createdAt = isset($this->data['created_at'])
                ? Carbon::parse($this->data['created_at'])->format('Y-m-d H:i:s')
                : now();

            // Cari atau buat user
            $user = User::firstOrCreate(
                ['name' => $requestedByName],
                [
                    'email' => Str::slug($requestedByName) . '@kpi.com',
                    'password' => Hash::make('12345678'),
                ]
            );
            $user->assignRole('User');

            // Cari atau buat department
            $department = Department::firstOrCreate(['name' => $departmentName]);

            // Cari atau buat user_department
            $userDepartment = UserDepartment::firstOrCreate([
                'user_id' => $user->id,
                'department_id' => $department->id,
            ]);

            // Simpan Purchase Requisition baru
            $purchaseRequisition = PurchaseRequisition::create([
                'number' => $this->data['number'],
                'purchase_type_id' => $purchaseTypeId,
                'description' => $this->data['description'],
                'requested_by' => $userDepartment->id,
                'department_id' => $department->id,
                'status' => $this->data['status'] ?? 0,
                'created_at' => $createdAt,
                'approved_at' => $this->data['approved_at'] ?? null,
                'cancelled_at' => $this->data['cancelled_at'] ?? null,
            ]);

            Log::info('Purchase Requisition berhasil dibuat:', ['id' => $purchaseRequisition->id]);

            // **Pastikan items terkait dibuat atau diperbarui**
            if (!empty($this->data['items'])) {
                $this->syncItemsWithPurchaseRequisition($purchaseRequisition, $this->data['items']);
            }

            return $purchaseRequisition; // Kembalikan PR baru yang dibuat
        } catch (\Exception $e) {
            Log::error('Error saat import:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function syncItemsWithPurchaseRequisition(PurchaseRequisition $purchaseRequisition, string $itemsJson): void
    {
        try {
            // Menghapus karakter escape yang berlebihan
            $itemsJson = stripslashes($itemsJson);

            // Periksa apakah itemsJson adalah array JSON atau string biasa
            if ($this->isJsonArray($itemsJson)) {
                $items = json_decode($itemsJson, true);
            } else {
                // Jika hanya string biasa, ubah menjadi array JSON dengan qty = 1 & unit_price = 0
                $items = [['name' => trim($itemsJson), 'qty' => 1, 'unit_price' => 0]];
            }

            // Periksa apakah JSON valid setelah decode
            if (!is_array($items)) {
                Log::error('JSON Decode Error:', ['error' => json_last_error_msg(), 'itemsJson' => $itemsJson]);
                return;
            }

            Log::info('Items berhasil di-decode:', ['items' => $items]);

            foreach ($items as $itemData) {
                $itemName = trim($itemData['name']);
                $itemQty = $itemData['qty'] ?? 1;
                $itemUnitPrice = isset($itemData['unit_price']) && is_numeric($itemData['unit_price'])
                    ? $itemData['unit_price']
                    : 0;

                // Cek apakah item sudah ada di tabel items
                $existingItem = Item::firstOrCreate(
                    ['name' => $itemName],
                    [
                        'sku' => str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT),
                        'unit' => 'unit',
                        'unit_price' => $itemUnitPrice,
                        'description' => $purchaseRequisition->description,
                    ]
                );

                Log::info('Item ditemukan atau dibuat:', ['id' => $existingItem->id, 'name' => $existingItem->name]);

                // Simpan atau perbarui di purchase_requisition_items
                $existingPRItem = PurchaseRequisitionItem::updateOrCreate(
                    [
                        'purchase_requisition_id' => $purchaseRequisition->id,
                        'item_id' => $existingItem->id,
                    ],
                    [
                        'qty' => $itemQty,
                        'unit_price' => $itemUnitPrice,
                        'total_price' => $itemQty * $itemUnitPrice,
                    ]
                );

                Log::info('Item tersinkronisasi dengan PR:', [
                    'purchase_requisition_id' => $purchaseRequisition->id,
                    'item_id' => $existingItem->id,
                    'qty' => $itemQty,
                    'unit_price' => $itemUnitPrice,
                    'total_price' => $itemQty * $itemUnitPrice,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error saat sinkronisasi items dengan PR:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Fungsi untuk mengecek apakah sebuah string adalah JSON array yang valid.
     */
    private function isJsonArray($string)
    {
        json_decode($string, true);
        return json_last_error() === JSON_ERROR_NONE && str_starts_with(trim($string), '[');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your purchase requisition import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
