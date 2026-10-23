<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenWeatherService
{
    public function __construct(
        protected ?string $apiKey = null,
        protected string $baseUrl = 'https://api.openweathermap.org',
        protected int $cacheTtl = 1800,
    ) {
        $this->apiKey = $this->apiKey ?? (string) config('services.openweather.key', '');
        $this->baseUrl = (string) config('services.openweather.base_url', 'https://api.openweathermap.org');
        $this->cacheTtl = (int) config('services.openweather.cache_ttl', 1800);
    }

    /**
     * Determine if an API key is configured.
     */
    public function hasApiKey(): bool
    {
        return ! empty($this->apiKey);
    }

    /**
     * Retrieve geographical coordinates for a city name.
     *
     * @return array{name: string, lat: float, lon: float, country: string, state?: string}|null
     */
    public function getCoordinates(string $cityName): ?array
    {
        $normalizedCity = trim(mb_strtolower($cityName));

        if ($normalizedCity === '') {
            return null;
        }

        $cacheKey = 'openweather:geo:'.Str::slug($normalizedCity);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($normalizedCity, $cityName): ?array {
            if (! $this->hasApiKey()) {
                return $this->getMockCoordinates($cityName);
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/geo/1.0/direct", [
                'q' => $normalizedCity,
                'limit' => 1,
                'appid' => $this->apiKey,
            ]);

            if ($response->successful() && ! empty($response->json())) {
                $item = $response->json()[0];

                return [
                    'name' => (string) ($item['name'] ?? $cityName),
                    'lat' => (float) ($item['lat'] ?? 0.0),
                    'lon' => (float) ($item['lon'] ?? 0.0),
                    'country' => (string) ($item['country'] ?? ''),
                    'state' => (string) ($item['state'] ?? ''),
                ];
            }

            return $this->getMockCoordinates($cityName);
        });
    }

    /**
     * Retrieve current weather for a city name.
     *
     * @return array<string, mixed>|null
     */
    public function getCurrentWeather(string $cityName): ?array
    {
        $normalizedCity = trim(mb_strtolower($cityName));

        if ($normalizedCity === '') {
            return null;
        }

        $cacheKey = 'openweather:weather:'.Str::slug($normalizedCity);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($normalizedCity, $cityName): ?array {
            if (! $this->hasApiKey()) {
                return $this->getMockCurrentWeather($cityName);
            }

            $coords = $this->getCoordinates($cityName);

            $params = [
                'units' => 'metric',
                'appid' => $this->apiKey,
            ];

            if ($coords !== null) {
                $params['lat'] = $coords['lat'];
                $params['lon'] = $coords['lon'];
            } else {
                $params['q'] = $normalizedCity;
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/data/2.5/weather", $params);

            if ($response->successful()) {
                $data = $response->json();
                $data['is_mock'] = false;

                return $data;
            }

            return $this->getMockCurrentWeather($cityName);
        });
    }

    /**
     * Retrieve 5-day weather forecast for a city name.
     *
     * @return array<string, mixed>|null
     */
    public function getForecast(string $cityName): ?array
    {
        $normalizedCity = trim(mb_strtolower($cityName));

        if ($normalizedCity === '') {
            return null;
        }

        $cacheKey = 'openweather:forecast:'.Str::slug($normalizedCity);

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($normalizedCity, $cityName): ?array {
            if (! $this->hasApiKey()) {
                return $this->getMockForecast($cityName);
            }

            $coords = $this->getCoordinates($cityName);

            $params = [
                'units' => 'metric',
                'appid' => $this->apiKey,
            ];

            if ($coords !== null) {
                $params['lat'] = $coords['lat'];
                $params['lon'] = $coords['lon'];
            } else {
                $params['q'] = $normalizedCity;
            }

            $response = Http::timeout(10)->get("{$this->baseUrl}/data/2.5/forecast", $params);

            if ($response->successful()) {
                $data = $response->json();
                $data['is_mock'] = false;

                return $data;
            }

            return $this->getMockForecast($cityName);
        });
    }

    /**
     * Format forecast data grouped by day for easy presentation in views and export.
     *
     * @param  array<string, mixed>  $forecastData
     * @return array<int, array{
     *     date: string,
     *     day_name: string,
     *     temp: float,
     *     temp_min: float,
     *     temp_max: float,
     *     humidity: int,
     *     wind_speed: float,
     *     description: string,
     *     icon: string
     * }>
     */
    public function formatDailyForecast(array $forecastData): array
    {
        $list = $forecastData['list'] ?? [];
        $days = [];

        foreach ($list as $entry) {
            $date = Carbon::parse($entry['dt_txt'] ?? $entry['dt'])->format('Y-m-d');

            if (! isset($days[$date])) {
                $days[$date] = [
                    'date' => $date,
                    'day_name' => Carbon::parse($date)->format('l'),
                    'temps' => [],
                    'humidity' => (int) ($entry['main']['humidity'] ?? 50),
                    'wind_speed' => (float) ($entry['wind']['speed'] ?? 0),
                    'description' => (string) ($entry['weather'][0]['description'] ?? 'Sunny'),
                    'icon' => (string) ($entry['weather'][0]['icon'] ?? '01d'),
                ];
            }

            $temp = (float) ($entry['main']['temp'] ?? 20);
            $days[$date]['temps'][] = $temp;
        }

        $formatted = [];
        foreach ($days as $date => $item) {
            $temps = $item['temps'];
            $formatted[] = [
                'date' => $date,
                'day_name' => $item['day_name'],
                'temp' => round(array_sum($temps) / count($temps), 1),
                'temp_min' => round(min($temps), 1),
                'temp_max' => round(max($temps), 1),
                'humidity' => $item['humidity'],
                'wind_speed' => $item['wind_speed'],
                'description' => $item['description'],
                'icon' => $item['icon'],
            ];
        }

        return array_slice($formatted, 0, 5);
    }

    /**
     * Provide mock coordinates for demonstration when API key is missing.
     *
     * @return array{name: string, lat: float, lon: float, country: string, state: string}
     */
    protected function getMockCoordinates(string $cityName): array
    {
        return [
            'name' => ucfirst($cityName),
            'lat' => 48.8566,
            'lon' => 2.3522,
            'country' => 'FR',
            'state' => 'Ile-de-France',
        ];
    }

    /**
     * Provide mock current weather data for preview when API key is not configured.
     *
     * @return array<string, mixed>
     */
    protected function getMockCurrentWeather(string $cityName): array
    {
        $now = Carbon::now();

        return [
            'name' => ucfirst($cityName),
            'coord' => [
                'lat' => 48.8566,
                'lon' => 2.3522,
            ],
            'weather' => [
                [
                    'id' => 800,
                    'main' => 'Clear',
                    'description' => 'scattered clouds',
                    'icon' => '02d',
                ],
            ],
            'main' => [
                'temp' => 18.5,
                'feels_like' => 17.8,
                'temp_min' => 14.2,
                'temp_max' => 21.0,
                'pressure' => 1016,
                'humidity' => 62,
            ],
            'visibility' => 10000,
            'wind' => [
                'speed' => 3.6,
                'deg' => 210,
            ],
            'clouds' => [
                'all' => 25,
            ],
            'sys' => [
                'country' => 'FR',
                'sunrise' => $now->copy()->startOfDay()->addHours(7)->timestamp,
                'sunset' => $now->copy()->startOfDay()->addHours(19)->timestamp,
            ],
            'dt' => $now->timestamp,
            'is_mock' => true,
        ];
    }

    /**
     * Provide mock 5-day forecast data for preview when API key is not configured.
     *
     * @return array<string, mixed>
     */
    protected function getMockForecast(string $cityName): array
    {
        $list = [];
        $baseDate = Carbon::now();

        $conditions = [
            ['description' => 'clear sky', 'icon' => '01d', 'temp' => 19.5],
            ['description' => 'few clouds', 'icon' => '02d', 'temp' => 18.0],
            ['description' => 'scattered clouds', 'icon' => '03d', 'temp' => 16.5],
            ['description' => 'light rain', 'icon' => '10d', 'temp' => 15.0],
            ['description' => 'partly cloudy', 'icon' => '02d', 'temp' => 17.2],
        ];

        for ($day = 0; $day < 5; $day++) {
            $date = $baseDate->copy()->addDays($day);
            $cond = $conditions[$day % count($conditions)];

            foreach (['09:00:00', '12:00:00', '15:00:00', '18:00:00'] as $time) {
                $list[] = [
                    'dt' => $date->copy()->setTimeFromTimeString($time)->timestamp,
                    'dt_txt' => $date->format('Y-m-d').' '.$time,
                    'main' => [
                        'temp' => $cond['temp'] + (rand(-15, 15) / 10),
                        'temp_min' => $cond['temp'] - 2.5,
                        'temp_max' => $cond['temp'] + 2.5,
                        'pressure' => 1015,
                        'humidity' => rand(50, 75),
                    ],
                    'weather' => [
                        [
                            'id' => 800,
                            'main' => 'Clouds',
                            'description' => $cond['description'],
                            'icon' => $cond['icon'],
                        ],
                    ],
                    'wind' => [
                        'speed' => rand(20, 55) / 10,
                    ],
                ];
            }
        }

        return [
            'city' => [
                'name' => ucfirst($cityName),
                'country' => 'FR',
            ],
            'list' => $list,
            'is_mock' => true,
        ];
    }
}
