<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $tzvi = User::create([
            'first_name' => 'Tzvi',
            'last_name' => 'Gettenberg',
            'email' => 'tzvigettenberg@gmail.com',
            'is_member' => false,
            'phone' => null,
        ]);

        $tzvi->assignRole('Super-Admin');

        $alex = User::create([
            'first_name' => 'Alex',
            'last_name' => 'Bucur',
            'email' => 'raiux0888@gmail.com',
            'is_member' => false,
            'phone' => null,
        ]);

        $alex->assignRole('Super-Admin');
    }
}
