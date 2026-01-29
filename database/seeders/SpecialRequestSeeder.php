<?php

namespace Database\Seeders;

use App\Models\SpecialRequest;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecialRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // SpecialRequest::truncate();
        $specialRequests = [
            [
                'title' => 'Abandon Entire Package',
                'type' => 'abandon_package',
                'description' => "Permanently remove this package form this suite.",
                'price' => 0,
            ],
            [
                'title' => 'Advance photos',
                'type' => 'advance_photos',
                'description' => "Request high-resolution photos of your package content.",
                'price' => 7,
            ],
            [
                'title' => 'Inspect Package',
                'type' => 'inspect_package',
                'description' => "Our team will inspect the contents of your package. Please allow up to three business days to complete.",
                'price' => 7,
            ],
            [
                'title' => 'Return to Sender',
                'type' => 'return_to_sender',
                'description' => "Send the package back to the merchant with or without a prepaid shipping label.",
                'price' => 7,
            ],
            [
                'title' => 'Basic Photos',
                'type' => 'basic_photos',
                'description' => "See basic photos taken of your package when it arrived at our facility.",
                'price' => 0,
            ],

        ];

        foreach ($specialRequests as $request) {
            SpecialRequest::updateOrCreate(
                ['type' => $request['type']],
                $request
            );
        }
    }
}
