<?php

namespace Database\Seeders;

use App\Models\Department;
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
            'Finance',
            'Human Resources',
            'IT',
            'Marketing',
        ];

        $departmentIds = [];

        foreach ($departments as $deptName) {
            $department = Department::firstOrCreate(['name' => $deptName]);
            $departmentIds[$deptName] = $department->id;
        }

        // Permissions for users
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

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $roles = [
            'Administrator' => [
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
            ],
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

        // Create users and assign roles with department
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'Administrator',
                'department' => 'IT', // Assign to IT department
            ],
            [
                'name' => 'User',
                'email' => 'user@starter.com',
                'password' => Hash::make('12345678'),
                'role' => 'User',
                'department' => 'Marketing', // Assign to Marketing department
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                ]
            );

            // Assign role
            $user->assignRole($userData['role']);

            // Attach department using pivot table
            $departmentId = $departmentIds[$userData['department']] ?? null;
            if ($departmentId) {
                $user->departments()->syncWithoutDetaching([$departmentId]);
            }
        }

        $this->command->info('Roles, Permissions, Departments, and Users have been created successfully!');
    }
}
