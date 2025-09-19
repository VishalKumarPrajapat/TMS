<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'id' => Role::ADMIN,
                'name' => 'admin',
                'permissions' => json_encode(['*'])
            ],
            [
                'id' => Role::MANAGER,
                'name' => 'manager',
                'permissions' => json_encode(['view_all_tasks', 'assign_tasks', 'edit_all_tasks'])
            ],
            [
                'id' => Role::USER,
                'name' => 'user',
                'permissions' => json_encode(['view_own_tasks', 'create_tasks', 'edit_own_tasks'])
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
