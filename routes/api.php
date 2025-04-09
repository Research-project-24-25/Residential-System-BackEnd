<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\HouseController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\MeetingRequestController; // Added
use Illuminate\Support\Facades\Route;

// Public routes - accessible to anyone
// Public Property Routes
Route::get('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/{type}/{id}', [PropertyController::class, 'show'])->whereIn('type', ['apartment', 'house'])->name('properties.show');

// Public Meeting Request Routes
Route::post('meeting-requests', [MeetingRequestController::class, 'store'])->name('meeting-requests.store');
Route::get('verify-meeting/{token}', [MeetingRequestController::class, 'verify'])->name('meeting-requests.verify');

// Admin authentication
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login');

// Resident authentication
Route::post('resident/login', [ResidentAuthController::class, 'login'])->name('resident.login');

// Admin authenticated routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Protected resource routes
    Route::apiResource('buildings', BuildingController::class);
    Route::apiResource('floors', FloorController::class);
    Route::apiResource('apartments', ApartmentController::class);
    Route::apiResource('houses', HouseController::class);
    Route::apiResource('residents', ResidentController::class);
});

// Super admin only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin:super_admin'])->group(function () {
    Route::post('register', [AdminAuthController::class, 'register'])->name('admin.register');
});

// Resident authenticated routes
Route::prefix('resident')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [ResidentAuthController::class, 'logout'])->name('resident.logout');
    Route::get('profile', [ResidentAuthController::class, 'profile'])->name('resident.profile');

    // Additional resident-specific routes will go here
});
