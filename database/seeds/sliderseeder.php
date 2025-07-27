<?php

namespace Database\Seeders;

use App\Models\Slider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class sliderseeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            Slider::insert([
            [
                'type' => 'slider2',
                'photo' => 'public/assets/img/placeholder.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'slider2',
                'photo' => 'public/assets/img/placeholder.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'slider1',
                'photo' => 'public/assets/img/placeholder.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
