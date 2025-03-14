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
                    // Tambahkan kolom lain yang perlu diupdate
                ]);

                Log::info('Purchase Requisition berhasil diperbarui:', ['id' => $existingPR->id]);

                // Proses items jika ada perubahan
                if (!empty($this->data['items'])) {
                    $itemsString = $this->data['items'];

                    // Menghapus escape karakter berlebih
                    $itemsString = stripslashes($itemsString);  // Menghapus backslashes yang berlebihan

                    // Parsing JSON
                    $items = json_decode($itemsString, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        Log::error('JSON Decode Error:', ['error' => json_last_error_msg()]);
                    } else {
                        // Log untuk melihat hasil decode JSON
                        Log::info('Items berhasil di-decode:', ['items' => $items]);

                        // Menambahkan qty default jika tidak ada
                        foreach ($items as &$item) {
                            if (!isset($item['qty'])) {
                                $item['qty'] = 1; // Assign default qty jika tidak ada
                            }

                            // Validasi unit_price
                            if (!isset($item['unit_price']) || !is_numeric($item['unit_price'])) {
                                $item['unit_price'] = 0; // Assign default unit_price jika tidak ada atau tidak valid
                            }
                        }

                        // Log setelah menambahkan qty dan unit_price default
                        Log::info('Items dengan qty dan unit_price default:', ['items' => $items]);
                    }

                    // Periksa apakah hasil decode adalah array
                    if (is_array($items)) {
                        foreach ($items as $itemData) {
                            $itemName = trim($itemData['name']);
                            $itemQty = $itemData['qty'];
                            $itemUnitPrice = $itemData['unit_price'];

                            // Cek apakah item sudah ada di tabel items
                            $existingItem = Item::firstOrCreate(
                                ['name' => $itemName],
                                [
                                    'sku' => str_pad(mt_rand(0, 99999), 5, '0', STR_PAD_LEFT),
                                    'unit' => 'unit',
                                    'unit_price' => $itemUnitPrice,
                                    'description' => $this->data['description'],
                                ]
                            );

                            Log::info('Item ditemukan atau dibuat:', ['id' => $existingItem->id, 'name' => $existingItem->name]);

                            // Simpan ke purchase_requisition_items jika belum ada
                            $existingPRItem = PurchaseRequisitionItem::where([
                                'purchase_requisition_id' => $existingPR->id,
                                'item_id' => $existingItem->id,
                            ])->first();

                            if (!$existingPRItem) {
                                PurchaseRequisitionItem::create([
                                    'purchase_requisition_id' => $existingPR->id,
                                    'item_id' => $existingItem->id,
                                    'qty' => $itemQty,
                                    'unit_price' => $itemUnitPrice,
                                    'total_price' => $itemQty * $itemUnitPrice,
                                ]);

                                Log::info('Item berhasil ditambahkan ke Purchase Requisition:', [
                                    'purchase_requisition_id' => $existingPR->id,
                                    'item_name' => $existingItem->name,
                                ]);
                            } else {
                                Log::info('Item sudah ada di Purchase Requisition:', [
                                    'purchase_requisition_id' => $existingPR->id,
                                    'item_name' => $existingItem->name,
                                ]);
                            }
                        }
                    } else {
                        Log::error('Format data items tidak valid:', ['items' => $this->data['items']]);
                        Log::info('Format data items:', ['items' => $this->data['items']]);
                    }
                }

                return $existingPR; // Kembalikan PR yang sudah diperbarui
            }

            // Jika tidak ada, buat Purchase Requisition baru
            $requestedByName = trim($this->data['requested_by']);
            $departmentName = trim($this->data['department_id']);

            // Ekstrak ID dari purchase_type_id
            $purchaseTypeIdRaw = trim($this->data['purchase_type_id']);
            $purchaseTypeId = (int) current(explode('.', $purchaseTypeIdRaw));

            Log::info('Ekstrak Purchase Type ID:', [
                'raw' => $purchaseTypeIdRaw,
                'extracted' => $purchaseTypeId
            ]);

            // Format created_at
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

            Log::info('User ditemukan atau dibuat:', ['id' => $user->id, 'name' => $user->name]);

            // Cari atau buat department
            $department = Department::firstOrCreate(['name' => $departmentName]);

            Log::info('Department ditemukan atau dibuat:', ['id' => $department->id, 'name' => $department->name]);

            // Simpan Purchase Requisition baru
            $purchaseRequisition = PurchaseRequisition::create([
                'number' => $this->data['number'],
                'purchase_type_id' => $purchaseTypeId,
                'description' => $this->data['description'],
                'requested_by' => $user->id,
                'department_id' => $department->id,
                'status' => $this->data['status'] ?? 0,
                'created_at' => $createdAt,
                'approved_at' => $this->data['approved_at'] ?? null,
                'cancelled_at' => $this->data['cancelled_at'] ?? null,
            ]);

            Log::info('Purchase Requisition berhasil dibuat:', ['id' => $purchaseRequisition->id]);

            return $purchaseRequisition; // Kembalikan PR baru yang dibuat
        } catch (\Exception $e) {
            Log::error('Error saat import:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
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
