<?php

// Controllers
use App\Http\Controllers\{
    AdminController,
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
    ResidentController,
    ServiceController,
    PropertyServiceController,
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
    Route::post('auth/register', 'register');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', 'logout');
        Route::get('auth/profile', 'profile');
        Route::put('auth/profile/{userId?}', 'updateProfile');
        Route::patch('auth/profile/{userId?}', 'updateProfile');
    });
});

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::controller(EmailVerificationController::class)
    ->prefix('email')
    ->name('verification.')
    ->group(function () {
        // This route validates email tokens
        Route::get('/verify/{id}/{hash}', 'verify')
            ->middleware('signed')
            ->name('verify');

        // Authenticated routes for email verification
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/resend', 'resend')->name('resend');
            Route::get('/status', 'status')->name('status');
        });
    });

/*
|--------------------------------------------------------------------------
| Public Properties
|--------------------------------------------------------------------------
*/

Route::controller(PropertyController::class)
    ->prefix('properties')
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/filter', 'filter');
        Route::get('/{id}', 'show');
    });

/*
|--------------------------------------------------------------------------
| Authenticated User Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'verified'])
    ->group(function () {
        // Meeting Requests
        Route::controller(MeetingRequestController::class)
            ->group(function () {
                Route::apiResource('meeting-requests', MeetingRequestController::class)->except(['destroy']);
                Route::post('meeting-requests/filter', 'filter');
                Route::patch('meeting-requests/{meeting_request}/cancel', 'cancel');
            });

        // Notifications
        Route::controller(NotificationController::class)
            ->group(function () {
                Route::apiResource('notifications', NotificationController::class)->only(['index', 'destroy']);
                Route::patch('notifications/{notification}/read', 'markAsRead');
                Route::patch('notifications/read-all', 'markAllAsRead');
            });
    });

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin:admin'])
    ->group(function () {
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
                Route::get('/revenues', 'revenues');
                Route::get('/expenditures', 'expenditures');
                Route::get('/profits', 'profits');
                Route::get('/financial-summary', 'financialSummary');
            });

        // Properties
        Route::controller(PropertyController::class)
            ->group(function () {
                Route::get('properties/trashed', 'trashed');
                Route::apiResource('properties', PropertyController::class);
                Route::post('properties/filter', 'filter');
                Route::patch('properties/{id}/restore', 'restore');
                Route::delete('properties/{id}/force', 'forceDelete');
            });

        // Residents
        Route::controller(ResidentController::class)->group(function () {
            Route::get('residents/trashed', 'trashed');
            Route::apiResource('residents', ResidentController::class);
            Route::post('residents/filter', 'filter');
            Route::patch('residents/{id}/restore', 'restore');
            Route::delete('residents/{id}/force', 'forceDelete');
        });

        // Services
        Route::controller(ServiceController::class)
            ->group(function () {
                Route::get('services/trashed', 'trashed');
                Route::apiResource('services', ServiceController::class);
                Route::post('services/filter', 'filter');
                Route::patch('services/{id}/restore', 'restore');
                Route::delete('services/{id}/force', 'forceDelete');
                Route::patch('services/{service}/toggle-active', 'toggleActive');
            });

        // Property-Service Management
        Route::controller(PropertyServiceController::class)
            ->group(function () {
                Route::post('properties/services', 'attach');
                Route::get('properties/{propertyId}/services', 'propertyServices');
                Route::get('services/{serviceId}/properties', 'serviceProperties');
                Route::get('properties/{propertyId}/services/{serviceId}', 'show');
                Route::put('properties/{propertyId}/services/{serviceId}', 'update');
                Route::delete('properties/{propertyId}/services/{serviceId}', 'detach');
                Route::patch('properties/{propertyId}/services/{serviceId}/activate', 'activate');
                Route::patch('properties/{propertyId}/services/{serviceId}/deactivate', 'deactivate');
                Route::post('properties/{propertyId}/services/generate-bills', 'generateBills');
            });

        // Bills and Payments
        Route::controller(BillController::class)
            ->group(function () {
                Route::get('bills/trashed', 'trashed');
                Route::apiResource('bills', BillController::class);
                Route::patch('bills/{id}/restore', 'restore');
                Route::delete('bills/{id}/force', 'forceDelete');
                Route::post('bills/generate-recurring', 'generateRecurringBills');
                Route::get('properties/{propertyId}/bills', 'propertyBills');
                Route::get('residents/{residentId}/bills', 'residentBills');
            });

        Route::controller(PaymentController::class)
            ->group(function () {
                Route::get('payments/trashed', 'trashed');
                Route::apiResource('payments', PaymentController::class);
                Route::patch('payments/{id}/restore', 'restore');
                Route::delete('payments/{id}/force', 'forceDelete');
                Route::get('bills/{billId}/payments', 'billPayments');
                Route::get('residents/{residentId}/payments', 'residentPayments');
            });

        // Maintenance
        Route::controller(MaintenanceController::class)
            ->group(function () {
                Route::get('maintenance-types/trashed', 'trashed');
                Route::apiResource('maintenance-types', MaintenanceController::class);
                Route::post('maintenance-types/filter', 'filter');
                Route::patch('maintenance-types/{id}/restore', 'restore');
                Route::delete('maintenance-types/{id}/force', 'forceDelete');
            });

        Route::controller(MaintenanceRequestController::class)
            ->group(function () {
                Route::get('maintenance-requests/trashed', 'trashed');
                Route::apiResource('maintenance-requests', MaintenanceRequestController::class);
                Route::post('maintenance-requests/filter', 'filter');
                Route::patch('maintenance-requests/{id}/restore', 'restore');
                Route::delete('maintenance-requests/{id}/force', 'forceDelete');
                Route::get('maintenance-requests/properties/{propertyId}', 'propertyMaintenanceRequests');
                Route::get('maintenance-requests/residents/{residentId}', 'residentMaintenanceRequests');
            });

        Route::controller(MaintenanceFeedbackController::class)->group(function () {
            Route::get('maintenance-feedback/trashed', 'trashed');
            Route::apiResource('maintenance-feedback', MaintenanceFeedbackController::class)
                ->only(['index', 'store', 'show', 'destroy']);
            Route::patch('maintenance-feedback/{id}/restore', 'restore');
            Route::delete('maintenance-feedback/{id}/force', 'forceDelete');
        });

        // Admin Management (super admin only)
        Route::middleware(['admin:super_admin'])->group(function () {
            Route::controller(AdminController::class)->group(function () {
                Route::get('admins/trashed', 'trashed');
                Route::apiResource('admins', AdminController::class);
                Route::post('admins/filter', 'filter');
                Route::patch('admins/{id}/restore', 'restore');
                Route::delete('admins/{id}/force', 'forceDelete');
            });
        });
    });

/*
|--------------------------------------------------------------------------
| Resident Routes
|--------------------------------------------------------------------------
*/

Route::prefix('resident')
    ->middleware(['auth:sanctum', 'resident'])
    ->group(function () {

        // Bills and Payments
        Route::controller(BillController::class)
            ->group(function () {
                Route::apiResource('bills', BillController::class)->only(['index', 'show']);
                Route::post('bills/filter', 'filter');
            });

        Route::controller(PaymentController::class)
            ->group(function () {
                Route::apiResource('payments', PaymentController::class)->only(['index', 'show']);
                Route::post('payments/filter', 'filter');
            });

        // Maintenance
        Route::controller(MaintenanceController::class)
            ->prefix('maintenance-types')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{maintenance_type}', 'show');
                Route::post('/filter', 'filter');
                Route::get('/categories/list', 'categories');
            });

        Route::controller(MaintenanceRequestController::class)
            ->group(function () {
                Route::apiResource('maintenance-requests', MaintenanceRequestController::class)
                    ->except(['destroy'])
                    ->parameters(['maintenance-requests' => 'maintenance_request']);
                Route::post('maintenance-requests/filter', 'filter');
                Route::patch('maintenance-requests/{maintenance_request}/cancel', 'cancel');
                Route::get('maintenance-requests/properties/{propertyId}', 'propertyMaintenanceRequests');
            });

        // Maintenance Feedback
        Route::controller(MaintenanceFeedbackController::class)
            ->group(function () {
                Route::apiResource('maintenance-requests.feedback', MaintenanceFeedbackController::class)
                    ->only(['store', 'show', 'update']);
                Route::get('feedback', 'residentFeedback')->middleware('substitute_auth_id');
            });

        // Property Services
        Route::controller(PropertyServiceController::class)
            ->group(function () {
                Route::get('properties/{propertyId}/services', 'propertyServices');
                Route::get('properties/{propertyId}/services/{serviceId}', 'show');
            });
    });
