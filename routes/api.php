<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\MeetingRequestController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::post('properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('properties/{id}', [PropertyController::class, 'show'])->name('properties.show');

// Meeting Request Routes
// Route for authenticated users to create and manage their meeting requests
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('properties', PropertyController::class)->except(['index', 'show']);

    // Get all meeting requests for the authenticated user
    Route::get('meeting-requests', [MeetingRequestController::class, 'index'])->name('meeting-requests.index');

    // Create a new meeting request
    Route::post('meeting-requests', [MeetingRequestController::class, 'store'])->name('meeting-requests.store');

    // Get a specific meeting request
    Route::get('meeting-requests/{id}', [MeetingRequestController::class, 'show'])->name('meeting-requests.show');

    // Cancel a meeting request (user can cancel their own requests)
    Route::patch('meeting-requests/{id}/cancel', [MeetingRequestController::class, 'cancel'])->name('meeting-requests.cancel');

    // Get upcoming meetings for the authenticated user
    Route::get('meeting-requests/upcoming', [MeetingRequestController::class, 'upcoming'])->name('meeting-requests.upcoming');

    // Notification Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::get('/{id}', [NotificationController::class, 'show'])->name('notifications.show');
        Route::patch('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
    });
});

// Admin authentication
Route::post('admin/login', [AdminAuthController::class, 'login'])->name('admin.login');

// Resident authentication
Route::post('resident/login', [ResidentAuthController::class, 'login'])->name('resident.login');

// Admin authenticated routes
Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('profile', [AdminAuthController::class, 'profile'])->name('admin.profile');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    Route::apiResource('residents', ResidentController::class);

    Route::patch('meeting-requests/{id}', [MeetingRequestController::class, 'update'])->name('admin.meeting-requests.update');

    Route::delete('meeting-requests/{id}', [MeetingRequestController::class, 'destroy'])->name('admin.meeting-requests.destroy');

    Route::get('meeting-requests', [MeetingRequestController::class, 'index'])->name('admin.meeting-requests.index');

    Route::get('meeting-requests/{id}', [MeetingRequestController::class, 'show'])->name('admin.meeting-requests.show');
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
