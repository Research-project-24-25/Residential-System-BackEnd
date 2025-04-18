<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\MeetingRequestController;
use App\Http\Controllers\User\AuthController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/{id}', [PropertyController::class, 'show'])->name('properties.show');

// User authentication
Route::post('register', [AuthController::class, 'register'])->name('user.register');
Route::post('login', [AuthController::class, 'login'])->name('user.login');
Route::delete('logout', [AuthController::class, 'logout'])->name('user.logout');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Get all meeting requests for the authenticated user
    Route::get('meeting-requests', [MeetingRequestController::class, 'index'])->name('meeting-requests.index');

    // Create a new meeting request
    Route::post('meeting-requests', [MeetingRequestController::class, 'store'])->name('meeting-requests.store');

    // Get a specific meeting request
    Route::get('meeting-requests/{id}', [MeetingRequestController::class, 'show'])->name('meeting-requests.show');

    // Cancel a meeting request (user can cancel their own requests)
    Route::patch('meeting-requests/{id}/cancel', [MeetingRequestController::class, 'cancel'])->name('meeting-requests.cancel');

    // Get upcoming meetings for the authenticated user
    Route::get('upcoming-meetings', [MeetingRequestController::class, 'upcoming'])->name('meeting-requests.upcoming');
});

// Admin authentication
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login');

// Resident authentication
Route::post('resident/login', [ResidentAuthController::class, 'login'])->name('resident.login');

// Admin authenticated routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('properties', PropertyController::class)->except(['index', 'show']);

    Route::get('profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::apiResource('residents', ResidentController::class);

    // Admin meeting request management endpoints
    Route::get('meeting-requests', [MeetingRequestController::class, 'index'])->name('admin.meeting-requests.index');
    Route::get('meeting-requests/{id}', [MeetingRequestController::class, 'show'])->name('admin.meeting-requests.show');
    Route::patch('meeting-requests/{id}', [MeetingRequestController::class, 'update'])->name('admin.meeting-requests.update');
    Route::delete('meeting-requests/{id}', [MeetingRequestController::class, 'destroy'])->name('admin.meeting-requests.destroy');
});

// Super admin only routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin:super_admin'])->group(function () {
    Route::post('register', [AdminAuthController::class, 'register'])->name('admin.register');
});

// Resident authenticated routes
Route::prefix('resident')->middleware('auth:sanctum')->group(function () {
    Route::post('logout', [ResidentAuthController::class, 'logout'])->name('resident.logout');
    Route::get('profile', [ResidentAuthController::class, 'profile'])->name('resident.profile');
});
