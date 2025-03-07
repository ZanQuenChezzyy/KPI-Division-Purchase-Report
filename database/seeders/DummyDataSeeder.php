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

        // Seed Departments
        $departments = [];
        for ($i = 0; $i < 15; $i++) {
            Department::create([
                'name' => Str::limit($faker->company . ' Department', 45, ''), // Batasi 45 karakter tanpa ellipsis
            ]);
            $departments[] = $i + 1;
            $this->showProgress($i + 1, 15, 'Departments');
        }
        $this->command->newLine(); // Tambah baris baru setelah Departments

        // Seed Users and UserDepartments
        $users = [];
        for ($i = 0; $i < 15; $i++) {
            $user = User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
                'password' => bcrypt('password'),
            ]);

            $users[] = $user->id;

            UserDepartment::create([
                'user_id' => $user->id,
                'department_id' => $faker->randomElement($departments),
            ]);
            $user->assignRole('User');
            $this->showProgress($i + 1, 15, 'Users');
        }
        $this->command->newLine(); // Tambah baris baru setelah Users

        // Seed Purchase Types
        $purchaseTypes = ['Goods', 'Services', 'Consultancy'];
        foreach ($purchaseTypes as $index => $type) {
            PurchaseType::create(['name' => $type]);
            $this->showProgress($index + 1, count($purchaseTypes), 'Purchase Types');
        }
        $this->command->newLine(); // Tambah baris baru setelah Purchase Types

        // Seed Vendors
        $vendors = [];
        $vendorCount = 1761;
        for ($i = 0; $i < $vendorCount; $i++) {
            $vendor = Vendor::create([
                'name' => $faker->company,
                'type' => $faker->randomElement(['Local', 'International']),
            ]);
            $vendors[] = $vendor->id;
            $this->showProgress($i + 1, $vendorCount, 'Vendors');
        }
        $this->command->newLine(); // Tambah baris baru setelah Vendors

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
        $this->command->newLine(); // Tambah baris baru setelah Items

        $requisitions = [];
        $requisitionCount = 1761;

        for ($i = 0; $i < $requisitionCount; $i++) {
            $status = $faker->numberBetween(0, 2); // Tentukan status terlebih dahulu

            $requisition = PurchaseRequisition::create([
                'number' => $faker->unique()->numerify('#####'),
                'purchase_type_id' => $faker->numberBetween(1, 3),
                'description' => $faker->sentence,
                'requested_by' => $faker->randomElement($users),
                'department_id' => $faker->randomElement($departments),
                'status' => $status,
                'approved_at' => $status === 2 ? $faker->dateTimeThisYear() : null, // Approved only if status === 2
                'cancelled_at' => $status === 1 ? $faker->dateTimeThisYear() : null, // Cancelled only if status === 1
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

        // Filter requisitions dengan status = 2
        $approvedRequisitions = PurchaseRequisition::where('status', 2)->pluck('id')->toArray();

        // Seed Purchase Orders hanya dari requisition yang statusnya = 2
        if (empty($approvedRequisitions)) {
            $this->command->warn("\nTidak ada Purchase Requisition dengan status 2.");
        } else {
            $poCount = count($approvedRequisitions); // Total PO berdasarkan requisitions
            $targetFullyReceived = ceil($poCount / 2); // Setengah dari PO harus fully received
            $targetNotConfirmed = ceil($poCount / 3); // 1/3 dari total PO harus tidak confirmed
            $fullyReceivedCounter = 0; // Counter untuk PO yang fully received
            $notConfirmedCounter = 0;  // Counter untuk PO yang tidak confirmed

            foreach ($approvedRequisitions as $index => $approvedRequisitionId) {
                $makeFullyReceived = $fullyReceivedCounter < $targetFullyReceived;
                $makeNotConfirmed = $notConfirmedCounter < $targetNotConfirmed;

                $po = PurchaseOrder::create([
                    'purchase_requisition_id' => $approvedRequisitionId,
                    'vendor_id' => $faker->randomElement($vendors),
                    'buyer' => $faker->randomElement($users),
                    'eta' => $faker->numerify('######'),
                    'mar_no' => $faker->numerify('######'),
                    'is_confirmed' => !$makeNotConfirmed, // 1/3 dari PO akan `false`
                    'is_received' => false, // Akan diupdate jika semua lines diterima
                    'is_closed' => false,   // Akan diupdate jika semua diterima
                    'confirmed_at' => $makeNotConfirmed ? null : $faker->dateTimeThisYear(),
                    'received_at' => $faker->dateTimeThisYear(),
                    'closed_at' => $faker->dateTimeThisYear(),
                ]);

                $this->showProgress($index + 1, $poCount, 'Purchase Orders');

                $allLinesReceived = true; // Flag untuk cek apakah semua lines diterima

                // Seed Purchase Order Lines
                for ($j = 0; $j < 15; $j++) {
                    $qty = $faker->numberBetween(1, 10); // Jumlah barang
                    $unitPrice = $faker->randomFloat(2, 100000, 50000000); // Harga per unit

                    // Jika ingin PO ini fully received
                    if ($makeFullyReceived) {
                        $receivedQty = $qty;
                        $status = 2; // Fully received
                    } else {
                        $receivedQty = $faker->numberBetween(0, $qty);
                        $status = $receivedQty == $qty ? 2 : $faker->numberBetween(0, 1); // Random status jika tidak full
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
                        'total_price' => $qty * $unitPrice, // Hitung total_price berdasarkan qty * unit_price
                        'received_qty' => $receivedQty,
                        'status' => $status,
                        'description' => $faker->sentence,
                    ]);
                }

                // Update is_received dan is_closed jika semua lines diterima, kecuali untuk yang is_confirmed = false
                if (($allLinesReceived || $makeFullyReceived) && !$makeNotConfirmed) {
                    $po->update([
                        'is_received' => true,
                        'is_closed' => true,
                    ]);
                    $fullyReceivedCounter++; // Tambah counter untuk PO yang fully received
                }

                // Tambah counter untuk PO yang tidak confirmed
                if ($makeNotConfirmed) {
                    $notConfirmedCounter++;
                }
            }
        }

        $this->command->newLine();
        $this->command->info("\nSeeding data selesai ✅");
    }

    /**
     * Show progress in the console.
     */
    private function showProgress(int $current, int $total, string $section): void
    {
        $percent = intval(($current / $total) * 100);
        $this->command->getOutput()->write("\rSeeding $section: ($percent%)");
    }
}
