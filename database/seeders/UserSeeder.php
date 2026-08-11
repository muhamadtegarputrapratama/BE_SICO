<?php
namespace Database\Seeders;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
public function run(): void
{
    $admin = User::firstOrCreate(
        ['email' => 'windum@gmail.com'],
        [
            'nama' => 'Admin SICO',
            'password' => Hash::make('Windum123'),
        ]
    );
    $admin->assignRole('admin');

    $atasan = User::firstOrCreate(
        ['email' => 'pungdum@gmail.com'],
        [
            'nama' => 'Atasan SICO',
            'password' => Hash::make('Pungdum123'),
        ]
    );
    $atasan->assignRole('atasan');

    $perpus = User::firstOrCreate(
        ['email' => 'pustakawan@gmail.com'],
        [
            'nama' => 'Pustakawan SICO',
            'password' => Hash::make('PustakaIPB2026'),
        ]
    );
    $perpus->assignRole('pustakawan');

}
}
