<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::updateOrCreate(
            ['email' => 'admin@conexa.pe'],
            ['name' => 'Administrador Conexa', 'password' => bcrypt('Admin123!'), 'rol' => 'admin', 'debe_cambiar_password' => true]
        );
    }
}
