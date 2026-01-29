<?php

namespace Database\Seeders;

use App\Models\LoginOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LoginOptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // LoginOption::truncate();
        $options = [
            [
                'title' => 'Additional Email Address (Optional)',
                'description' => 'We will send notifications to this email address and the email we have on file.',
                'is_text_input' => true,
            ],
            [
                'title' => 'Take photos of items at login',
                'description' => 'Request basic photos of your items when they arrive (3.50 USD)',
                'is_text_input' => false,
            ],
        ];

        foreach ($options as $option) {
            LoginOption::updateOrCreate(
                ['title' => $option['title']],
                $option
            );
        }
    }
}
