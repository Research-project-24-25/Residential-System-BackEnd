<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentAuthController;
use Illuminate\Support\Facades\Route;

// Public routes - accessible to anyone

// Buildings, floors and apartments public access
Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
Route::get('buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show');

Route::get('floors', [FloorController::class, 'index'])->name('floors.index');
Route::get('floors/{floor}', [FloorController::class, 'show'])->name('floors.show');

Route::get('apartments', [ApartmentController::class, 'index'])->name('apartments.index');
Route::get('apartments/{apartment}', [ApartmentController::class, 'show'])->name('apartments.show');

Route::get('houses', [HouseController::class, 'index'])->name('houses.index');
Route::get('houses/{house}', [HouseController::class, 'show'])->name('houses.show');

// Admin authentication
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login');

// Resident authentication
Route::post('resident/login', [ResidentAuthController::class, 'login'])->name('resident.login');


// Admin authenticated routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::post('register', [AdminAuthController::class, 'register'])->name('admin.register');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
    Route::get('profile', [AdminAuthController::class, 'profile'])->name('admin.profile');

    // Protected resource routes
    Route::apiResource('buildings', BuildingController::class)->except(['index', 'show']);
    Route::apiResource('floors', FloorController::class)->except(['index', 'show']);
    Route::apiResource('apartments', ApartmentController::class)->except(['index', 'show']);
    Route::apiResource('houses', HouseController::class)->except(['index', 'show']);
    Route::apiResource('residents', ResidentController::class);
});

// Resident authenticated routes
Route::prefix('resident')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [ResidentAuthController::class, 'logout'])->name('resident.logout');
    Route::get('profile', [ResidentAuthController::class, 'profile'])->name('resident.profile');

    // Additional resident-specific routes will go here
});
