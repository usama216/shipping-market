<?php

namespace Database\Seeders;

use App\Models\PreferredShipMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PreferredShipMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PreferredShipMethod::firstOrCreate(
            ['title' => 'Preferred Express'],
            [
                'title' => 'Preferred Express',
                'description' => 'Preferred Express is the most affordable and reliable way to get your package quickly. We choose the best carrier for you, based on the shipments destination.',
            ]
        );
    }
}
