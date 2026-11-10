<?php

use App\Http\Controllers\Api\ForecastController;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/weather/dijon', WeatherController::class)->name('weather.dijon');
Route::get('/geocoding', GeocodingController::class)->name('geocoding');
Route::get('/forecast', ForecastController::class)->name('forecast');
