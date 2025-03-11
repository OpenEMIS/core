<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api-docs-{version}.json', function ($version) {
    $filePath = public_path("api-docs-{$version}.json");

    if (!File::exists($filePath)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    return Response::file($filePath);
});
