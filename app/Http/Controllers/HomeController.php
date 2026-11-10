<?php

namespace App\Http\Controllers;

use App\Services\OpenWeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Page d'accueil avec la météo actuelle de Dijon.
     */
    public function __invoke(OpenWeatherService $weather): View
    {
        try {
            $current = $weather->currentWeather('Dijon');
        } catch (RequestException|ConnectionException $e) {
            report($e);
            $current = null;
        }

        return view('home', ['weather' => $current]);
    }
}
