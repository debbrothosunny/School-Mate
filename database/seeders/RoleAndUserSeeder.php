<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User; // Make sure to import the User model

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
    */
    
    public function run(): void
    {
        // Clear cache for roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Create Roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $accountsRole = Role::firstOrCreate(['name' => 'accounts']);

        // 2. Create sample users and assign roles (if they don't exist)
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@example.com'], // Check by email
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'), // Use a strong password in production!
                'email_verified_at' => now(),
                'contact_info' => '01XXXXXXXXX' // Example contact info
            ]
        );
        $adminUser->assignRole($adminRole); // Assign the 'admin' role

        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Teacher User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'contact_info' => '01XXXXXXXXX'
            ]
        );
        $teacherUser->assignRole($teacherRole); // Assign the 'teacher' role

        $accountsUser = User::firstOrCreate(
            ['email' => 'accounts@example.com'],
            [
                'name' => 'Accounts User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'contact_info' => '01XXXXXXXXX'
            ]
        );
        $accountsUser->assignRole($accountsRole); // Assign the 'accounts' role

        $normalUser = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Normal User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'contact_info' => '01XXXXXXXXX'
            ]
        );
        // This user will have no specific role assigned initially
    }
}