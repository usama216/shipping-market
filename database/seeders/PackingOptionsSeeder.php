<?php

namespace Database\Seeders;

use App\Models\PackingOptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackingOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // PackingOptions::truncate();
        $options = [
            [
                'title' => 'Fragile Stickers',
                'description' => 'We will apply fragile stickers to every box in your shipment (2.00 USD)',
                'price' => 2.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Discard Shoe Boxes',
                'description' => 'We will discard shoe boxes during packing, which may reduce the size and cost of your shipment (5.00 USD)',
                'price' => 5.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Extra Padding',
                'description' => 'We will use extra protective packaging when packing your shipment (5.00 USD)',
                'price' => 5.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Ship in Original Boxes',
                'description' => 'We will ship your packages in the same boxes that were received, if possible.',
                'price' => 0.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Only Single Outbound Parcel Shipments',
                'description' => 'We will send a one-parcel outbound shipment (5.00 USD).',
                'price' => 5.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Place Documents in Envelope',
                'description' => 'All documents will ship together in an envelope (2.00 USD)',
                'price' => 2.00,
                'is_text_input' => false,
            ],
            [
                'title' => 'Maximum Weight per Box',
                'description' => 'We will create a multi-piece shipment with this weight limit on each box. Please enter weight in pounds (1 lb = .46 kg). (7.00 USD)',
                'price' => 7.00,
                'is_text_input' => true,
            ],
        ];

        foreach ($options as $option) {
            PackingOptions::updateOrCreate(
                ['title' => $option['title']],
                $option
            );
        }
    }
}
