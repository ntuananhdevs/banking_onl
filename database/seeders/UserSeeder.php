<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo user admin
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'balance' => 1000000, // 1,000,000 VNĐ
        ]);

        // Tạo user thường
        User::create([
            'name' => 'Nguyễn Văn A',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'balance' => 500000, // 500,000 VNĐ
        ]);

        User::create([
            'name' => 'Trần Thị B',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'balance' => 250000, // 250,000 VNĐ
        ]);

        User::create([
            'name' => 'Lê Văn C',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'balance' => 0,
        ]);

        $this->command->info('Đã tạo 4 users mẫu:');
        $this->command->info('1. admin@example.com / password (Admin - 1,000,000 VNĐ)');
        $this->command->info('2. user1@example.com / password (500,000 VNĐ)');
        $this->command->info('3. user2@example.com / password (250,000 VNĐ)');
        $this->command->info('4. user3@example.com / password (0 VNĐ)');
    }
}
