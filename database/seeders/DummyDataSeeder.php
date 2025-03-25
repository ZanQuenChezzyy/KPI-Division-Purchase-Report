<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\PurchaseType;
use App\Models\User;
use App\Models\UserDepartment;
use App\Models\Vendor;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;


class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        $this->command->info("Memulai seeding data...");

        // Ambil ID Purchase Type dan Department dari database
        $purchaseTypes = [
            'None' => 0,
            'Direct Purchase' => 1,
            'Indirect Purchase' => 2,
            'Stock Item' => 3,
            'Shutdown Plant' => 4,
            'Global Purchase' => 5,
            'Outsourcing' => 6,
            'Consumable' => 9,
        ];

        $departments = [
            'General Affair' => 1,
            'Human Resource' => 2,
            'Maintenance' => 3,
            'Information Technology' => 4,
            'Project' => 5,
            'PE/Lab' => 6,
            'QSHE' => 7,
            'Production' => 8,
            'Logistic' => 9,
            'Shipping' => 10,
        ];

        $vendorTypes = [
            'International' => 0,
            'Domestic' => 1,
        ];

        // Seed Users
        $users = [];
        for ($i = 0; $i < 30; $i++) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
                'department_id' => $faker->randomElement(array_values($departments)), // Pilih ID secara acak dari daftar department
            ]);

            $users[] = $user->id;
            $user->assignRole('User');
            $this->showProgress($i + 1, 30, 'Users');
        }
        $this->command->newLine();

        // Seed Vendors
        $vendors = [];
        $vendorCount = 1761;
        for ($i = 0; $i < $vendorCount; $i++) {
            $vendor = Vendor::create([
                'name' => $faker->company,
                'type' => $faker->randomElement(array_values($vendorTypes)),
            ]);
            $vendors[] = $vendor->id;
            $this->showProgress($i + 1, $vendorCount, 'Vendors');
        }
        $this->command->newLine();

        // Seed Items
        $items = [];
        $itemCount = 1761;
        for ($i = 0; $i < $itemCount; $i++) {
            $item = Item::create([
                'name' => $faker->word,
                'sku' => $faker->unique()->numerify('SKU-#####'),
                'unit' => $faker->randomElement(['pcs', 'kg', 'liters']),
                'unit_price' => $faker->randomFloat(0, 50000, 10000000),
                'description' => $faker->sentence,
            ]);
            $items[] = $item->id;
            $this->showProgress($i + 1, $itemCount, 'Items');
        }
        $this->command->newLine();

        // Seed Purchase Requisitions
        $requisitions = [];
        $requisitionCount = 1761;
        for ($i = 0; $i < $requisitionCount; $i++) {
            $requestedBy = $faker->randomElement($users); // Pilih user secara acak
            $user = User::find($requestedBy); // Ambil data user untuk mendapatkan department_id

            $status = $faker->numberBetween(0, 2); // Tentukan status terlebih dahulu

            $requisition = PurchaseRequisition::create([
                'number' => $faker->unique()->numerify('#####'),
                'purchase_type_id' => $faker->randomElement(array_values($purchaseTypes)),
                'description' => $faker->sentence,
                'requested_by' => $requestedBy, // User yang mengajukan
                'department_id' => $user->department_id, // Ambil department_id dari user yang dipilih
                'status' => $status,
                'approved_at' => $status === 2 ? $faker->dateTimeThisYear() : null,
                'cancelled_at' => $status === 1 ? $faker->dateTimeThisYear() : null,
            ]);

            $requisitions[] = $requisition->id;
            $this->showProgress($i + 1, $requisitionCount, 'Requisitions');

            // Seed Purchase Requisition Items
            for ($j = 0; $j < 15; $j++) {
                $qty = $faker->numberBetween(1, 20);
                $unitPrice = $faker->randomFloat(2, 100000, 50000000);

                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'item_id' => $faker->randomElement($items),
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'total_price' => $qty * $unitPrice,
                ]);
            }
        }
        $this->command->newLine();

        // Filter hanya requisition dengan status = 2
        $approvedRequisitions = PurchaseRequisition::where('status', 2)->pluck('id')->toArray();

        if (empty($approvedRequisitions)) {
            $this->command->warn("\nTidak ada Purchase Requisition dengan status 2.");
        } else {
            $poCount = count($approvedRequisitions);
            $targetFullyReceived = ceil($poCount / 2);
            $targetNotConfirmed = ceil($poCount / 3);
            $fullyReceivedCounter = 0;
            $notConfirmedCounter = 0;
            $logisticUsers = User::whereHas('department', function ($query) {
                $query->where('name', 'Logistic'); // Sesuaikan jika nama berbeda
            })->pluck('id')->toArray();
            foreach ($approvedRequisitions as $index => $approvedRequisitionId) {
                $makeFullyReceived = $fullyReceivedCounter < $targetFullyReceived;
                $makeNotConfirmed = $notConfirmedCounter < $targetNotConfirmed;

                $po = PurchaseOrder::create([
                    'purchase_requisition_id' => $approvedRequisitionId,
                    'vendor_id' => $faker->randomElement($vendors),
                    'buyer' => !empty($logisticUsers) ? $faker->randomElement($logisticUsers) : null,
                    'eta' => $faker->numerify('######'),
                    'mar_no' => $faker->numerify('######'),
                    'created_by' => $faker->randomElement($users), // Pilih user dari yang sudah dibuat
                    'updated_by' => $faker->randomElement($users), // Pilih user dari yang sudah dibuat
                    'is_confirmed' => !$makeNotConfirmed,
                    'is_received' => false,
                    'is_closed' => false,
                    'confirmed_at' => $makeNotConfirmed ? null : $faker->dateTimeThisYear(),
                    'received_at' => $faker->dateTimeThisYear(),
                    'closed_at' => $faker->dateTimeThisYear(),
                ]);

                $this->showProgress($index + 1, $poCount, 'Purchase Orders');

                $allLinesReceived = true;

                for ($j = 0; $j < 15; $j++) {
                    $qty = $faker->numberBetween(1, 10);
                    $unitPrice = $faker->randomFloat(2, 100000, 50000000);

                    if ($makeFullyReceived) {
                        $receivedQty = $qty;
                        $status = 2;
                    } else {
                        $receivedQty = $faker->numberBetween(0, $qty);
                        $status = $receivedQty == $qty ? 2 : $faker->numberBetween(0, 1);
                        if ($receivedQty < $qty) {
                            $allLinesReceived = false;
                        }
                    }

                    PurchaseOrderLine::create([
                        'purchase_order_id' => $po->id,
                        'purchase_requisition_item_id' => $faker->randomElement($approvedRequisitions),
                        'item_id' => $faker->randomElement($items),
                        'qty' => $qty,
                        'unit_price' => $unitPrice,
                        'total_price' => $qty * $unitPrice,
                        'received_qty' => $receivedQty,
                        'status' => $status,
                        'description' => $faker->sentence,
                    ]);
                }

                if (($allLinesReceived || $makeFullyReceived) && !$makeNotConfirmed) {
                    $po->update([
                        'is_received' => true,
                        'is_closed' => true,
                    ]);
                    $fullyReceivedCounter++;
                }

                if ($makeNotConfirmed) {
                    $notConfirmedCounter++;
                }
            }
        }

        $this->command->newLine();
        $this->command->info("\nSeeding data selesai ✅");
    }

    private function showProgress(int $current, int $total, string $section): void
    {
        $percent = intval(($current / $total) * 100);
        $this->command->getOutput()->write("\rSeeding $section: ($percent%)");
    }
}
