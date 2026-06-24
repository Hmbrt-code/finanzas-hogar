<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Household;
use App\Models\Member;
use App\Models\User;

class HouseholdSeeder extends Seeder
{
    public function run(): void
    {
        // Toma el primer usuario registrado y le asigna un hogar
        $user = User::first();

        if (! $user) {
            return;
        }

        if ($user->member) {
            return; // ya tiene miembro asignado
        }

        $household = Household::create(['name' => 'Mi Hogar']);

        Member::create([
            'household_id' => $household->id,
            'user_id'      => $user->id,
            'name'         => $user->name,
            'role'         => 'admin',
        ]);
    }
}
