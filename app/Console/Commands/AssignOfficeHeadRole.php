<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AssignOfficeHeadRole extends Command
{
    protected $signature = 'users:assign-office-head';

    protected $description = 'Create an Office Head account or assign the role to an existing user';

    public function handle(): int
    {
        $role = Role::query()
            ->where('name', 'Office Head')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            $this->error(
                'The Office Head role does not exist. Check RolesAndPermissionsSeeder before continuing.'
            );

            return self::FAILURE;
        }

        $choice = $this->choice(
            'What would you like to do?',
            [
                'Assign the role to an existing user',
                'Create a new account and assign the role',
            ],
            0
        );

        if ($choice === 'Assign the role to an existing user') {
            return $this->assignToExistingUser($role);
        }

        return $this->createAccount($role);
    }

    private function assignToExistingUser(Role $role): int
    {
        $id = $this->ask('Enter the existing user ID');

        if (! ctype_digit((string) $id) || (int) $id < 1) {
            $this->error('Please enter a valid user ID.');

            return self::FAILURE;
        }

        $user = User::query()->find((int) $id);

        if (! $user) {
            $this->error('User not found. No changes were made.');

            return self::FAILURE;
        }

        $this->line('');
        $this->line('Selected account:');
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->name}");
        $this->line('Email: '.($user->email ?? 'No email'));

        if ($user->eligible_employee_id !== null) {
            $this->line('Account type: linked employee account');
        } else {
            $this->line('Account type: non-employee account');
        }

        $this->line(
            'Current roles: '.(
                $user->getRoleNames()->join(', ') ?: 'None'
            )
        );

        if ($user->hasRole($role)) {
            $this->warn('This user already has the Office Head role.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Assign the Office Head role to this user?', false)) {
            $this->warn('Cancelled. No changes were made.');

            return self::SUCCESS;
        }

        /*
         * assignRole adds the role without removing existing roles.
         * For example, an employee can keep the Employee role.
         */
        $user->assignRole($role);

        $this->info('The Office Head role was assigned successfully.');
        $this->line('Next: assign the user to an office in Office Head Assignments.');

        return self::SUCCESS;
    }

    private function createAccount(Role $role): int
    {
        $name = trim((string) $this->ask('Enter the Office Head’s name'));
        $email = strtolower(trim((string) $this->ask('Enter their email address')));

        $detailsValidator = Validator::make(
            [
                'name' => $name,
                'email' => $email,
            ],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ]
        );

        if ($detailsValidator->fails()) {
            foreach ($detailsValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Enter a password for the new account');
        $passwordConfirmation = (string) $this->secret('Confirm the password');

        $passwordValidator = Validator::make(
            [
                'password' => $password,
                'password_confirmation' => $passwordConfirmation,
            ],
            [
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(12)->letters()->numbers(),
                ],
            ]
        );

        if ($passwordValidator->fails()) {
            foreach ($passwordValidator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $this->line('');
        $this->line("Name: {$name}");
        $this->line("Email: {$email}");
        $this->line('Role: Office Head');

        /*
         * This is a manual verification decision by the administrator.
         * The application will not send a verification email here.
         */
        if (! $this->confirm(
            'Have you independently verified this email address and authorized this account?',
            false
        )) {
            $this->warn('Cancelled. No account was created.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($name, $email, $password, $role): void {
            $user = new User();

            $user->name = $name;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->email_verified_at = now();

            $user->save();

            $user->assignRole($role);
        });

        $this->info('Office Head account created successfully.');
        $this->line('The user can log in using their email address and password.');
        $this->line('Next: assign the user to an office in Office Head Assignments.');

        return self::SUCCESS;
    }
}
