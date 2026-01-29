<?php

namespace Database\Seeders;

use App\Models\InternationalShippingOptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class InternationalShippingOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            [
                'title' => 'DHL Express',
                'description' => '2 to 4 business days',
                'status' => 'active',
            ],
            [
                'title' => 'FedEx Economy',
                'description' => '2 to 4 business days',
                'status' => 'active',
            ],
            [
                'title' => 'UPS Economy',
                'description' => '3 to 4 business days',
                'status' => 'active',
            ],
            [
                'title' => 'Sea-freight',
                'description' => '7 to 10 business days',
                'status' => 'inactive',
            ],
            [
                'title' => 'Air-Cargo',
                'description' => '5 to 7 business days',
                'status' => 'inactive',
            ],
        ];

        foreach ($options as $option) {
            InternationalShippingOptions::updateOrCreate(
                ['title' => $option['title']],
                $option
            );
        }
    }
}
