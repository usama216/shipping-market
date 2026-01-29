<?php

namespace Database\Seeders;

use App\Models\ShippingPreferenceOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingPreferenceOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ShippingPreferenceOption::truncate();
        $options = [
            [
                'title' => 'Enhanced Liability Protection',
                'description' => 'Protect your shipment against loss or damage for 2.99 USD per 100.00 USD of coverage. You cannot file a claim if Enhanced Liability Protection is not purchased.',
                'price' => 2.99,
            ],
            [
                'title' => 'Urgent Handling',
                'description' => 'Ship requests must be submitted before 3:00 PM ET (UTC/GMT -5 hours) Monday through Friday for same-day processing.',
                'price' => 0.00,
            ],
        ];

        foreach ($options as $option) {
            ShippingPreferenceOption::updateOrCreate(
                ['title' => $option['title']],
                $option
            );
        }
    }
}
