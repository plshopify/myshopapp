<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

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

// libxml_use_internal_errors(true);

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/install', [HomeController::class, 'installApp']);
Route::get('/', [HomeController::class, 'index']);

Route::get('writeFile', [HomeController::class, 'writeFile']);



