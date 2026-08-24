<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $permissions = [
            'verifikasi-pustaka',
            'verifikasi-admin',
            'verifikasi-atasan',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum',
            ]);
        }


        $pustakawan = Role::where('name', 'pustakawan')->where('guard_name', 'sanctum')->first();
        $pustakawan?->givePermissionTo('verifikasi-pustaka');

        $admin = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        $admin?->givePermissionTo('verifikasi-admin');

        $atasan = Role::where('name', 'atasan')->where('guard_name', 'sanctum')->first();
        $atasan?->givePermissionTo([
            'verifikasi-pustaka',
            'verifikasi-admin',
            'verifikasi-atasan',
        ]);
    }
    }

