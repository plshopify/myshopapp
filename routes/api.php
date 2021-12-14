<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ThemeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('applyChanges', [HomeController::class, 'applyChanges']);
Route::get('getThemes', [ThemeController::class, 'getThemes']);
Route::get('getThemeDetail/{id}', [ThemeController::class, 'getThemeDetail']);
