<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['owner' => 'Pemilik Toko', 'kasir' => 'Kasir Toko'] as $role => $name) {
            User::firstOrCreate(['username' => $role], ['name' => $name, 'email' => $role.'@binamandiri.test', 'password' => $role.'123', 'role' => $role]);
        }
    }
}
