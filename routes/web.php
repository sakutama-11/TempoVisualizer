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
Route::delete('/', [TempoVisualizerController::class, 'delete']);
Route::post('/', [TempoVisualizerController::class, 'post']);
Route::get('/result/{execId?}', [TempoVisualizerController::class, 'result'])->name('result');
Route::get('/sample', [TempoVisualizerController::class, 'sample']);
