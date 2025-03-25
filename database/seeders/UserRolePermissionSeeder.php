<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\PurchaseType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserRolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create departments
        $departments = [
            'General Affair',
            'Human Resource',
            'Maintenance',
            'Information Technology',
            'Project',
            'PE/Lab',
            'QSHE',
            'Production',
            'Logistic',
            'Shipping',
        ];

        $purchaseTypes = [
            ['id' => 0, 'name' => 'None'],
            ['id' => 1, 'name' => 'Direct Purchase'],
            ['id' => 2, 'name' => 'Indirect Purchase'],
            ['id' => 3, 'name' => 'Stock Item'],
            ['id' => 4, 'name' => 'Shutdown Plant'],
            ['id' => 5, 'name' => 'Global Purchase'],
            ['id' => 6, 'name' => 'Outsourcing'],
            ['id' => 9, 'name' => 'Consumable'],
        ];

        // Simpan department ke database dan ambil ID-nya
        $departmentIds = [];
        foreach ($departments as $deptName) {
            $department = Department::firstOrCreate(['name' => $deptName]);
            $departmentIds[$deptName] = $department->id;
        }

        // Simpan purchase types
        foreach ($purchaseTypes as $purchaseType) {
            PurchaseType::updateOrCreate(
                ['id' => $purchaseType['id']], // Mencari berdasarkan ID
                ['name' => $purchaseType['name']] // Jika ditemukan, update name
            );
        }

        // Permissions untuk users
        $permissions = [
            'Create Users',
            'Read Users',
            'Update Users',
            'Delete Users',
            'Create Purchase Requisitions',
            'Read Purchase Requisitions',
            'Update Purchase Requisitions',
            'Delete Purchase Requisitions',
            'Create Purchase Orders',
            'Read Purchase Orders',
            'Update Purchase Orders',
            'Delete Purchase Orders',
            'Create Departments',
            'Read Departments',
            'Update Departments',
            'Delete Departments',
            'Create Vendors',
            'Read Vendors',
            'Update Vendors',
            'Delete Vendors',
            'Create Items',
            'Read Items',
            'Update Items',
            'Delete Items',
            'Create Purchase Types',
            'Read Purchase Types',
            'Update Purchase Types',
            'Delete Purchase Types',
        ];

        // Buat permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Buat roles dan assign permissions
        $roles = [
            'Administrator' => $permissions, // Admin dapat semua permissions
            'User' => [
                'Create Purchase Requisitions',
                'Read Purchase Requisitions',
                'Update Purchase Requisitions',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }

        // Buat users dengan department_id langsung
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'Administrator',
                'department' => 'Information Technology', // Assign ke IT department
            ],
            [
                'name' => 'User',
                'email' => 'user@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'User',
                'department' => 'Logistic', // Assign ke Marketing department
            ],
        ];

        foreach ($users as $userData) {
            // Ambil department ID
            $departmentId = $departmentIds[$userData['department']] ?? null;

            // Jika department ID ditemukan, buat user
            if ($departmentId) {
                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'name' => $userData['name'],
                        'password' => $userData['password'],
                        'department_id' => $departmentId, // Simpan langsung di tabel users
                    ]
                );

                // Assign role
                $user->assignRole($userData['role']);
            }
        }

        $this->command->info('Roles, Permissions, Departments, Purchase Types, and Users have been created successfully!');
    }
}
