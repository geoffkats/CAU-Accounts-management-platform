<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CreateDefaultUsers extends Command
{
    protected $signature = 'users:create-defaults';
    protected $description = 'Create default admin and accountant users';

    public function handle(): int
    {
        $this->info('Creating default users...');

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@codeacademy.ug'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => 'admin',
            ]
        );

        if ($admin->wasRecentlyCreated) {
            $this->info('✓ Admin user created: admin@codeacademy.ug / password');
        } else {
            $this->info('✓ Admin user already exists: admin@codeacademy.ug');
        }

        // Create Accountant User
        $accountant = User::firstOrCreate(
            ['email' => 'accountant@codeacademy.ug'],
            [
                'name' => 'Accountant User',
                'password' => bcrypt('password'),
                'role' => 'accountant',
            ]
        );

        if ($accountant->wasRecentlyCreated) {
            $this->info('✓ Accountant user created: accountant@codeacademy.ug / password');
        } else {
            $this->info('✓ Accountant user already exists: accountant@codeacademy.ug');
        }

        $this->newLine();
        $this->warn('⚠️  IMPORTANT: Change these default passwords after first login!');
        $this->newLine();
        
        $this->table(
            ['Email', 'Password', 'Role'],
            [
                ['admin@codeacademy.ug', 'password', 'admin'],
                ['accountant@codeacademy.ug', 'password', 'accountant'],
            ]
        );

        return Command::SUCCESS;
    }
}
