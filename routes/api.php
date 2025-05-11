<?php

// Controllers
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\{
    AdminAuthController,
    AuthController,
    BillController,
    DashboardController,
    EmailVerificationController,
    MaintenanceController,
    MaintenanceFeedbackController,
    MaintenanceRequestController,
    MeetingRequestController,
    NotificationController,
    PaymentController,
    PropertyController,
    ResidentAuthController,
    ResidentController,
    ServiceController,
    ServiceRequestController
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Unified Authentication
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login');
    Route::middleware('auth:sanctum')->post('auth/logout', 'logout');
});

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::prefix('email')->group(function () {
    // Verify e-mail
    Route::get('/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed');

    // Resend link
    Route::middleware('auth:sanctum')
        ->post('/resend', [EmailVerificationController::class, 'resend']);
});

/*
|--------------------------------------------------------------------------
| Public resources & user auth
|--------------------------------------------------------------------------
*/
Route::controller(PropertyController::class)
    ->prefix('properties')
    ->group(function () {
        Route::get('/', 'index'); // Simple listing
        Route::post('/filter', 'filter'); // Listing with filters
        Route::get('/{id}', 'show');
    });

Route::post('auth/register', [UserAuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Authenticated user routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('meeting-requests', MeetingRequestController::class)
        ->except(['destroy']);
    Route::post('meeting-requests/filter', [MeetingRequestController::class, 'filter']);
    Route::patch('meeting-requests/{meeting_request}/cancel', [MeetingRequestController::class, 'cancel']);

    Route::apiResource('notifications', NotificationController::class)
        ->only(['index', 'destroy']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
});

/*
|--------------------------------------------------------------------------
| Admin routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin:admin'])
    ->group(function () {

        // Profile
        Route::get('profile', [AdminAuthController::class, 'profile']);

        // Properties
        Route::apiResource('properties', PropertyController::class);
        Route::post('properties/filter', [PropertyController::class, 'filter']);

        // Residents
        Route::apiResource('residents', ResidentController::class);
        Route::post('residents/filter', [ResidentController::class, 'filter']);

        // Meeting requests
        Route::apiResource('meeting-requests', MeetingRequestController::class);
        Route::post('meeting-requests/filter', [MeetingRequestController::class, 'filter']);

        // Dashboard
        Route::controller(DashboardController::class)
            ->prefix('dashboard')
            ->group(function () {
                Route::get('/', 'overview');
                Route::get('/recent-activity', 'recentActivity');
                Route::get('/revenue', 'revenue');
                Route::get('/properties', 'properties');
                Route::get('/users', 'users');
                Route::get('/services', 'services');
                Route::get('/properties-revenue', 'propertiesRevenue');
            });

        // Services
        Route::apiResource('services', ServiceController::class);
        Route::post('services/filter', [ServiceController::class, 'filter']);
        Route::patch('services/{service}/toggle-active', [ServiceController::class, 'toggleActive']);

        // Service Requests
        Route::apiResource('service-requests', ServiceRequestController::class);
        Route::post('service-requests/filter', [ServiceRequestController::class, 'filter']);
        Route::get('service-requests/properties/{propertyId}', [ServiceRequestController::class, 'propertyServiceRequests']);
        Route::get('service-requests/residents/{residentId}', [ServiceRequestController::class, 'residentServiceRequests']);

        // Bills
        Route::apiResource('bills', BillController::class);
        Route::post('bills/generate-recurring', [BillController::class, 'generateRecurringBills']);
        Route::get('properties/{propertyId}/bills', [BillController::class, 'propertyBills']);
        Route::get('residents/{residentId}/bills',  [BillController::class, 'residentBills']);

        // Payments
        Route::apiResource('payments', PaymentController::class)
            ->only(['index', 'store', 'show', 'update']);
        Route::get('bills/{billId}/payments', [PaymentController::class, 'billPayments']);
        Route::get('residents/{residentId}/payments', [PaymentController::class, 'residentPayments']);

        // Payment methods routes removed

        // Maintenance types
        Route::apiResource('maintenance-types', MaintenanceController::class);
        Route::post('maintenance-types/filter', [MaintenanceController::class, 'filter']);

        // Maintenance requests
        Route::apiResource('maintenance-requests', MaintenanceRequestController::class);
        Route::post('maintenance-requests/filter', [MaintenanceRequestController::class, 'filter']);
        Route::get('maintenance-requests/properties/{propertyId}', [MaintenanceRequestController::class, 'propertyMaintenanceRequests']);
        Route::get('maintenance-requests/residents/{residentId}',  [MaintenanceRequestController::class, 'residentMaintenanceRequests']);

        // Maintenance feedback
        Route::apiResource('maintenance-feedback', MaintenanceFeedbackController::class)
            ->only(['index', 'store', 'show', 'destroy']);
    });

/*
|--------------------------------------------------------------------------
| Super-admin only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin:super_admin'])
    ->post('admin/register', [AdminAuthController::class, 'register']);

/*
|--------------------------------------------------------------------------
| Resident routes
|--------------------------------------------------------------------------
*/
Route::prefix('resident')
    ->middleware(['auth:sanctum', 'resident'])
    ->group(function () {

        // Profile
        Route::get('profile', [ResidentAuthController::class, 'profile']);

        // Bills
        Route::apiResource('bills', BillController::class)
            ->only(['index', 'show']);
        Route::post('bills/filter', [BillController::class, 'filter']);

        // Payments
        Route::apiResource('payments', PaymentController::class)
            ->only(['index', 'show']);
        Route::post('payments/filter', [PaymentController::class, 'filter']);

        // Services
        Route::apiResource('services', ServiceController::class)
            ->only(['index', 'show'])
            ->parameters(['services' => 'service']);
        Route::post('services/filter', [ServiceController::class, 'filter']);

        // Service Requests
        Route::apiResource('service-requests', ServiceRequestController::class)
            ->except(['destroy'])
            ->parameters(['service-requests' => 'service_request']);
        Route::post('service-requests/filter', [ServiceRequestController::class, 'filter']);
        Route::patch('service-requests/{service_request}/cancel', [ServiceRequestController::class, 'cancel']);
        Route::get('service-requests/properties/{propertyId}', [ServiceRequestController::class, 'propertyServiceRequests']);

        // Maintenance types
        Route::apiResource('maintenance-types', MaintenanceController::class)
            ->only(['index', 'show'])
            ->parameters(['maintenance-types' => 'maintenance_type']);
        Route::post('maintenance-types/filter', [MaintenanceController::class, 'filter']);
        Route::get('maintenance-types/categories/list', [MaintenanceController::class, 'categories']);

        // Maintenance requests
        Route::apiResource('maintenance-requests', MaintenanceRequestController::class)
            ->except(['destroy'])
            ->parameters(['maintenance-requests' => 'maintenance_request']);
        Route::post('maintenance-requests/filter', [MaintenanceRequestController::class, 'filter']);
        Route::patch('maintenance-requests/{maintenance_request}/cancel', [MaintenanceRequestController::class, 'cancel']);
        Route::get('maintenance-requests/properties/{propertyId}', [MaintenanceRequestController::class, 'propertyMaintenanceRequests']);

        // Maintenance feedback
        Route::apiResource('maintenance-requests.feedback', MaintenanceFeedbackController::class)
            ->only(['store', 'show', 'update']);
        Route::get('feedback', [MaintenanceFeedbackController::class, 'residentFeedback'])
            ->middleware('substitute_auth_id');
    });
