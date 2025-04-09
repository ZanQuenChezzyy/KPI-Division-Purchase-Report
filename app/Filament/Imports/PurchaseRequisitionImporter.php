<?php

namespace App\Filament\Imports;

use App\Models\Department;
use App\Models\Item;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\purchase_type_id;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\Vendor;
use Carbon\Carbon;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Kelas Importer untuk data Purchase Requisition
class PurchaseRequisitionImporter extends Importer
{
    // Model utama yang digunakan oleh importer ini
    protected static ?string $model = PurchaseRequisition::class;

    // Fungsi ini menentukan kolom-kolom yang wajib ada dan bagaimana memetakannya saat import
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

    // Fungsi utama yang dipanggil saat tiap baris data diimpor; membuat atau update PR
    public function resolveRecord(): ?PurchaseRequisition
    {
        try {
            // Cek apakah PR dengan nomor yang sama sudah ada
            $existingPR = PurchaseRequisition::where('number', $this->data['number'])->first();

            if ($existingPR) {
                // Jika sudah ada, update data yang diperlukan
                Log::info('Purchase Requisition dengan nomor ini sudah ada:', ['number' => $this->data['number']]);

                $existingPR->update([
                    'description' => $this->data['description'] ?? $existingPR->description,
                    'status' => $this->data['status'] ?? $existingPR->status,
                    'approved_at' => $this->parseDate($this->data['approved_at']),
                    'cancelled_at' => $this->parseDate($this->data['cancelled_at']),
                ]);

                Log::info('Purchase Requisition diperbarui:', ['id' => $existingPR->id]);

                // Jika ada data items, sinkronisasi ulang
                if (!empty($this->data['items'])) {
                    $this->syncItemsWithPurchaseRequisition($existingPR, $this->data['items']);
                }

                return $existingPR;
            }

            // Jika belum ada, mulai proses buat PR baru
            $requestedByName = trim($this->data['requested_by']);
            $departmentName = trim($this->data['department_id']);
            $purchaseTypeId = (int) current(explode('.', trim($this->data['purchase_type_id'])));
            $department = Department::firstOrCreate(['name' => $departmentName]);

            // Cek dan parse tanggal created_at
            $createdAt = null;
            if (!empty($this->data['created_at'])) {
                try {
                    $createdAt = Carbon::parse($this->data['created_at'])->format('Y-m-d') . ' 00:00:00';
                } catch (\Exception $e) {
                    Log::warning('Format tanggal created_at tidak valid:', [
                        'input' => $this->data['created_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Jika tanggal tidak valid, hentikan import
            if (!$createdAt) {
                Log::error('Nilai created_at tidak valid atau kosong, tidak menyimpan PR:', [
                    'number' => $this->data['number'],
                ]);
                return null;
            }

            // Buat user jika belum ada
            $user = User::firstOrCreate(
                ['name' => $requestedByName],
                [
                    'email' => Str::slug($requestedByName) . '@kpi.com',
                    'password' => Hash::make('12345678'),
                    'department_id' => $department->id,
                ]
            );
            $user->assignRole('User');

            // Buat record PR baru
            $purchaseRequisition = PurchaseRequisition::create([
                'number' => $this->data['number'],
                'purchase_type_id' => $purchaseTypeId,
                'description' => $this->data['description'],
                'requested_by' => $user->id,
                'department_id' => $department->id,
                'status' => $this->data['status'] ?? 0,
                'created_at' => $createdAt,
                'approved_at' => $this->parseDate($this->data['approved_at']),
                'cancelled_at' => $this->parseDate($this->data['cancelled_at']),
            ]);

            Log::info('Purchase Requisition baru dibuat:', ['id' => $purchaseRequisition->id]);

            // Sinkronisasi item PR jika ada
            if (!empty($this->data['items'])) {
                $this->syncItemsWithPurchaseRequisition($purchaseRequisition, $this->data['items']);
            }

            // Jika status PR = 1, otomatis buatkan PO
            if ($purchaseRequisition->status == 1) {
                $this->createPurchaseOrderFromRequisition($purchaseRequisition);
            }

            return $purchaseRequisition;
        } catch (\Exception $e) {
            // Tangani error saat proses import
            Log::error('Error saat import:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // Fungsi untuk parse tanggal dari berbagai format ke format standar Y-m-d
    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        Log::warning("Format tanggal tidak dikenali: $date");
        return null;
    }

    // Fungsi untuk sinkronisasi antara PR dan item-itemnya
    private function syncItemsWithPurchaseRequisition(PurchaseRequisition $purchaseRequisition, string $itemsJson): void
    {
        try {
            $itemsJson = stripslashes($itemsJson);

            if ($this->isJsonArray($itemsJson)) {
                $items = json_decode($itemsJson, true);
            } else {
                $items = [['name' => trim($itemsJson), 'qty' => 1, 'unit_price' => 0]];
            }

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

                PurchaseRequisitionItem::updateOrCreate(
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

    // Fungsi untuk otomatis membuat Purchase Order dari PR jika status PR = 1
    private function createPurchaseOrderFromRequisition(PurchaseRequisition $purchaseRequisition)
    {
        try {
            Log::info('Membuat Purchase Order untuk PR:', ['pr_id' => $purchaseRequisition->id]);

            $vendorName = trim($this->data['vendor'] ?? '');
            $buyerName = trim($this->data['buyer'] ?? '');
            $departmentName = trim($this->data['department_id']);
            $vendorType = isset($this->data['vendor_type']) && is_numeric(trim($this->data['vendor_type']))
                ? (int) trim($this->data['vendor_type'])
                : 0;

            $vendor = !empty($vendorName)
                ? Vendor::firstOrCreate(['name' => $vendorName], ['type' => $vendorType])
                : null;

            $department = Department::firstOrCreate(['name' => $departmentName]);

            if (!empty($buyerName)) {
                $buyer = User::firstOrCreate(
                    ['name' => $buyerName],
                    [
                        'email' => Str::slug($buyerName) . '@kpi.com',
                        'password' => Hash::make('12345678'),
                        'department_id' => $department->id,
                    ]
                );
                $buyer->assignRole('User');
            } else {
                $buyer = User::find($purchaseRequisition->requested_by);
            }

            $purchaseOrder = PurchaseOrder::create([
                'purchase_requisition_id' => $purchaseRequisition->id,
                'vendor_id' => $vendor ? $vendor->id : null,
                'buyer' => $buyer->id,
                'eta' => null,
                'mar_no' => null,
                'is_confirmed' => true,
                'is_received' => false,
                'is_closed' => false,
                'confirmed_at' => $purchaseRequisition->created_at,
                'received_at' => null,
                'closed_at' => null,
                'created_by' => $purchaseRequisition->requested_by,
                'updated_by' => $purchaseRequisition->requested_by,
            ]);

            Log::info('Purchase Order berhasil dibuat:', ['po_id' => $purchaseOrder->id]);

            foreach ($purchaseRequisition->purchaseRequisitionItems as $prItem) {
                $existingPoLine = PurchaseOrderLine::where('purchase_order_id', $purchaseOrder->id)
                    ->where('purchase_requisition_item_id', $prItem->id)
                    ->first();

                if (!$existingPoLine) {
                    $poLine = PurchaseOrderLine::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_requisition_item_id' => $prItem->id,
                        'item_id' => $prItem->item_id,
                        'qty' => $prItem->qty,
                        'unit_price' => $prItem->unit_price,
                        'total_price' => $prItem->qty * $prItem->unit_price,
                        'received_qty' => $prItem->qty,
                        'status' => 1,
                        'description' => $prItem->Item->name,
                    ]);

                    Log::info('Purchase Order Line dibuat:', ['po_line_id' => $poLine->id]);
                } else {
                    Log::info('Purchase Order Line sudah ada, tidak dibuat ulang.', [
                        'po_line_id' => $existingPoLine->id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'purchase_requisition_item_id' => $prItem->id,
                    ]);
                }
            }

            Log::info('Purchase Order dan Order Lines selesai dibuat.', ['po_id' => $purchaseOrder->id]);

        } catch (\Exception $e) {
            Log::error('Error saat membuat Purchase Order:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    // Fungsi pengecekan apakah sebuah string adalah JSON array
    private function isJsonArray($string)
    {
        json_decode($string, true);
        return json_last_error() === JSON_ERROR_NONE && str_starts_with(trim($string), '[');
    }

    // Fungsi untuk menampilkan pesan notifikasi ketika proses import selesai
    public static function getCompletedNotificationBody(Import $import): string
    {
        return "Your import has completed with {$import->successful_rows} successful rows and {$import->getFailedRowsCount()} failed rows.";
    }
}
