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
            $existingPR = PurchaseRequisition::where('number', $this->data['number'])->first();

            if ($existingPR) {
                Log::info('Purchase Requisition dengan nomor ini sudah ada:', ['number' => $this->data['number']]);

                $existingPR->update([
                    'description' => $this->data['description'] ?? $existingPR->description,
                    'status' => $this->data['status'] ?? $existingPR->status,
                    'approved_at' => $this->parseDate($this->data['approved_at']),
                    'cancelled_at' => $this->parseDate($this->data['cancelled_at']),
                ]);

                Log::info('Purchase Requisition diperbarui:', ['id' => $existingPR->id]);

                if (!empty($this->data['items'])) {
                    $this->syncItemsWithPurchaseRequisition($existingPR, $this->data['items']);
                }

                return $existingPR;
            }

            $requestedByName = trim($this->data['requested_by']);
            $departmentName = trim($this->data['department_id']);
            $purchaseTypeId = (int) current(explode('.', trim($this->data['purchase_type_id'])));
            $department = Department::firstOrCreate(['name' => $departmentName]);

            $createdAt = null;

            // Pastikan data created_at tidak kosong
            if (!empty($this->data['created_at'])) {
                try {
                    // Parsing tanggal dan set waktu default 00:00:00
                    $createdAt = Carbon::parse($this->data['created_at'])->format('Y-m-d') . ' 00:00:00';
                } catch (\Exception $e) {
                    Log::warning('Format tanggal created_at tidak valid:', [
                        'input' => $this->data['created_at'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Jika parsing gagal atau null, jangan gunakan now(), tetap null
            if (!$createdAt) {
                Log::error('Nilai created_at tidak valid atau kosong, tidak menyimpan PR:', [
                    'number' => $this->data['number'],
                ]);
                return null;
            }


            $user = User::firstOrCreate(
                ['name' => $requestedByName],
                [
                    'email' => Str::slug($requestedByName) . '@kpi.com',
                    'password' => Hash::make('12345678'),
                    'department_id' => $department->id,
                ]
            );
            $user->assignRole('User');

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

            if (!empty($this->data['items'])) {
                $this->syncItemsWithPurchaseRequisition($purchaseRequisition, $this->data['items']);
            }

            // Jika status PR = 1, buat Purchase Order
        if ($purchaseRequisition->status == 1) {
            $this->createPurchaseOrderFromRequisition($purchaseRequisition);
        }

            return $purchaseRequisition;
        } catch (\Exception $e) {
            Log::error('Error saat import:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        // Coba beberapa format umum yang digunakan
        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        // Jika tidak cocok, log error dan return null
        Log::warning("Format tanggal tidak dikenali: $date");
        return null;
    }

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

    private function createPurchaseOrderFromRequisition(PurchaseRequisition $purchaseRequisition)
{
    try {
        Log::info('Membuat Purchase Order untuk PR:', ['pr_id' => $purchaseRequisition->id]);

        // Ambil nama vendor dan buyer dari data PR (pastikan kolom ini ada di CSV)
        $vendorName = trim($this->data['vendor'] ?? '');
        $buyerName = trim($this->data['buyer'] ?? '');
        $departmentName = trim($this->data['department_id']);
        
        // Cari atau buat vendor
        $vendor = !empty($vendorName) ? Vendor::firstOrCreate(['name' => $vendorName]) : null;
        $department = Department::firstOrCreate(['name' => $departmentName]);

        // Cari atau buat buyer (User)
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
            $buyer = User::find($purchaseRequisition->requested_by); // Default ke requested_by jika buyer kosong
        }

        $purchaseOrder = PurchaseOrder::create([
            'purchase_requisition_id' => $purchaseRequisition->id,
            'vendor_id' => $vendor ? $vendor->id : null, // Gunakan vendor yang ditemukan atau null
            'buyer' => $buyer->id, // Set buyer berdasarkan data dari CSV atau requested_by
            'eta' => null, // ETA bisa ditentukan nanti
            'mar_no' => null,
            'is_confirmed' => false,
            'is_received' => false,
            'is_closed' => false,
            'confirmed_at' => null,
            'received_at' => null,
            'closed_at' => null,
            'created_by' => $purchaseRequisition->requested_by,
            'updated_by' => $purchaseRequisition->requested_by,
        ]);

        Log::info('Purchase Order berhasil dibuat:', ['po_id' => $purchaseOrder->id]);

        foreach ($purchaseRequisition->purchaseRequisitionItems as $prItem) {
            $poLine = PurchaseOrderLine::create([
                'purchase_order_id' => $purchaseOrder->id,
                'purchase_requisition_item_id' => $prItem->id,
                'item_id' => $prItem->item_id,
                'qty' => $prItem->qty,
                'unit_price' => $prItem->unit_price,
                'total_price' => $prItem->qty * $prItem->unit_price,
                'received_qty' => 0,
                'status' => 'pending',
                'description' => $prItem->Item->name,
            ]);

            Log::info('Purchase Order Line dibuat:', ['po_line_id' => $poLine->id]);
        }

        Log::info('Purchase Order dan Order Lines selesai dibuat.', ['po_id' => $purchaseOrder->id]);

    } catch (\Exception $e) {
        Log::error('Error saat membuat Purchase Order:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
}


    private function isJsonArray($string)
    {
        json_decode($string, true);
        return json_last_error() === JSON_ERROR_NONE && str_starts_with(trim($string), '[');
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return "Your import has completed with {$import->successful_rows} successful rows and {$import->getFailedRowsCount()} failed rows.";
    }
}
