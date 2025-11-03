<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting database seeding...');

        // Create PM user
        $pm = User::firstOrCreate(
            ['email' => 'pm@example.com'],
            [
                'full_name' => 'Project Manager',
                'title' => 'Senior Project Manager',
                'password' => Hash::make('password'),
                'role' => 'PM',
                'is_active' => true,
            ]
        );
        $this->command->info("Created PM user: {$pm->email} (ID: {$pm->id})");

        // Create member user
        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'full_name' => 'Team Member',
                'title' => 'Developer',
                'password' => Hash::make('password'),
                'role' => 'MEMBER',
                'is_active' => true,
            ]
        );
        $this->command->info("Created Member user: {$member->email} (ID: {$member->id})");

        // Create fake users (mix of PM and MEMBER)
        $this->command->info('Creating 10 fake users...');
        $users = User::factory(10)->create([
            'role' => fake()->randomElement(['PM', 'MEMBER', 'MEMBER', 'MEMBER', 'MEMBER']),
            'is_active' => true,
        ]);

        $pmCount = $users->where('role', 'PM')->count();
        $memberCount = $users->where('role', 'MEMBER')->count();
        
        $this->command->info("Created {$users->count()} users: {$pmCount} PM, {$memberCount} MEMBER");
        
        $totalUsers = User::count();
        $this->command->info("Total users in database: {$totalUsers}");
        $this->command->info('Database seeding completed successfully!');
    }
}
