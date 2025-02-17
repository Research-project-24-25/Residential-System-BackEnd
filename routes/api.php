<?php

use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('buildings', BuildingController::class)->names('buildings');
Route::apiResource('floors', FloorController::class)->names('floors');
Route::apiResource('apartments', ApartmentController::class)->names('apartments');
