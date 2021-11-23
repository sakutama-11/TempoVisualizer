<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TempoVisualizerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [TempoVisualizerController::class, 'get']);
Route::get('/sample', [TempoVisualizerController::class, 'sample']);
Route::post('/', [TempoVisualizerController::class, 'post']);
