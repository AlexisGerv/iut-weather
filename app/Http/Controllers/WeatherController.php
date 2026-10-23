<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exports\ForecastExport;
use App\Services\OpenWeatherService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WeatherController extends Controller
{
    /**
     * Display the home page with weather and forecast information.
     */
    public function index(Request $request, OpenWeatherService $weatherService): View
    {
        $city = (string) $request->query('city', 'Paris');
        $city = trim($city);

        if ($city === '') {
            $city = 'Paris';
        }

        $weather = $weatherService->getCurrentWeather($city);
        $forecastRaw = $weatherService->getForecast($city);
        $forecast = $forecastRaw ? $weatherService->formatDailyForecast($forecastRaw) : [];
        $hasApiKey = $weatherService->hasApiKey();
        $isMock = (bool) ($weather['is_mock'] ?? false);

        return view('home', [
            'city' => $city,
            'weather' => $weather,
            'forecast' => $forecast,
            'hasApiKey' => $hasApiKey,
            'isMock' => $isMock,
        ]);
    }

    /**
     * Export weather forecast as a CSV spreadsheet.
     */
    public function export(Request $request, OpenWeatherService $weatherService): BinaryFileResponse
    {
        $city = (string) $request->query('city', 'Paris');
        $city = trim($city);

        if ($city === '') {
            $city = 'Paris';
        }

        $forecastRaw = $weatherService->getForecast($city);
        $forecast = $forecastRaw ? $weatherService->formatDailyForecast($forecastRaw) : [];

        $filename = Str::slug($city).'-forecast-'.now()->format('Y-m-d').'.csv';

        return Excel::download(new ForecastExport($forecast, $city), $filename, \Maatwebsite\Excel\Excel::CSV);
    }
}
