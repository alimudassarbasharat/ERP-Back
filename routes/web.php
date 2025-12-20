<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PDFTestController;
use App\Http\Controllers\StudentIDCardController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('students')->group(function () {
    // Removed report card routes from web.php; they are now in api.php
});
