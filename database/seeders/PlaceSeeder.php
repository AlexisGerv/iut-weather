<?php

namespace Database\Seeders;

use App\Models\Place;
use Illuminate\Database\Seeder;

class PlaceSeeder extends Seeder
{
    /**
     * Ajoute quelques villes françaises.
     */
    public function run(): void
    {
        $places = [
            ['name' => 'Dijon', 'slug' => 'dijon', 'latitude' => 47.3220, 'longitude' => 5.0415],
            ['name' => 'Paris', 'slug' => 'paris', 'latitude' => 48.8566, 'longitude' => 2.3522],
            ['name' => 'Lyon', 'slug' => 'lyon', 'latitude' => 45.7640, 'longitude' => 4.8357],
            ['name' => 'Besançon', 'slug' => 'besancon', 'latitude' => 47.2378, 'longitude' => 6.0241],
        ];

        foreach ($places as $place) {
            Place::updateOrCreate(['slug' => $place['slug']], $place);
        }
    }
}
