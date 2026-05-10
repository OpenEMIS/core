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
    //POCOR-9602: Read version from core root (works standalone or with CakePHP)
    $versionFile = base_path('../version');
    if (!file_exists($versionFile)) {
        $versionFile = base_path('version'); //POCOR-9602: fallback for standalone API deployment
    }
    $version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : 'N/A';
    return view('welcome', compact('version'));
});

Route::get('/api-docs-{version}.json', function ($version) {
    $filePath = public_path("api-docs-{$version}.json");

    if (!File::exists($filePath)) {
        return response()->json(['error' => 'File not found'], 404);
    }

    return Response::file($filePath);
});
