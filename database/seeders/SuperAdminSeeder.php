<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('services.super_admin.name');
        $email = config('services.super_admin.email');
        $password = config('services.super_admin.password');

        if (! $name || ! $email || ! $password) {
            $this->command?->error(
                'Set SUPER_ADMIN_NAME, SUPER_ADMIN_EMAIL, and SUPER_ADMIN_PASSWORD in .env first.'
            );

            return;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->command?->error('SUPER_ADMIN_EMAIL is not a valid email address.');

            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->warn(
                'A user with this email already exists. No changes were made.'
            );

            return;
        }

        $role = Role::where('name', 'Super Admin')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            $this->command?->error(
                'The Super Admin role does not exist. Run the roles seeder first.'
            );

            return;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),

            // This account is created manually by the system administrator.
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        $this->command?->info('Super Admin account created successfully.');
    }
}
