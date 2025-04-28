<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;

// Controllers
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ResidentAuthController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\MeetingRequestController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| Unified Authentication
|--------------------------------------------------------------------------
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('auth/login', 'login')->name('auth.login');
    Route::middleware('auth:sanctum')->post('auth/logout', 'logout')->name('auth.logout');
});

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/

Route::prefix('email')->name('verification.')->group(function () {
    // Verify e-mail
    Route::get('/verify/{id}/{hash}', function (Request $request, $id, $hash) {
        $user = User::findOrFail($id);

        abort_unless(
            hash_equals((string) $hash, sha1($user->getEmailForVerification())),
            403,
            'Invalid verification link.'
        );

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $user->markEmailAsVerified();
        return response()->json(['message' => 'Email verified successfully.']);
    })->middleware('signed')->name('verify');

    // Resend link
    Route::middleware('auth:sanctum')->post('/resend', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent.']);
    })->name('send');
});

/*
|--------------------------------------------------------------------------
| Public resources & user auth
|--------------------------------------------------------------------------
*/
Route::controller(PropertyController::class)
    ->prefix('properties')
    ->name('properties.')
    ->group(function () {
        Route::get('/', 'index')->name('index'); // Simple listing
        Route::post('/filter', 'filter')->name('filter'); // Listing with filters
        Route::get('/{id}', 'show')->name('show');
    });

Route::post('auth/register', [UserAuthController::class, 'register'])
    ->name('auth.register');

/*
|--------------------------------------------------------------------------
| Authenticated user routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::controller(MeetingRequestController::class)
        ->prefix('meeting-requests')
        ->name('meeting-requests.')
        ->group(function () {
            Route::get('/',           'index')->name('index');
            Route::post('/',          'store')->name('store');
            Route::get('/upcoming',   'upcoming')->name('upcoming');
            Route::get('/{id}',       'show')->name('show');
            Route::patch('/{id}/cancel', 'cancel')->name('cancel');
        });
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
        Route::get('profile', [AdminAuthController::class, 'profile'])
            ->name('admin.profile');

        Route::controller(PropertyController::class)
            ->prefix('properties')
            ->name('admin.properties.')
            ->group(function () {
                Route::get('/', 'index')->name('index');
                Route::post('/filter', 'filter')->name('filter');
                Route::post('/', 'store')->name('store');
                Route::get('/{id}', 'show')->name('show');
                Route::match(['put', 'patch'], '/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

        // Residents
        Route::apiResource('residents', ResidentController::class);

        // Meeting requests
        Route::controller(MeetingRequestController::class)
            ->prefix('meeting-requests')
            ->name('admin.meeting-requests.')
            ->group(function () {
                Route::get('/',       'index')->name('index');
                Route::get('/{id}',   'show')->name('show');
                Route::patch('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
            });

        /*
    |------------------------------------------------------------------
    | Billing
    |------------------------------------------------------------------
    */
        // Bills
        Route::controller(BillController::class)->group(function () {
            Route::get('bills',                    'index')->name('admin.bills.index');
            Route::post('bills',                   'store')->name('admin.bills.store');
            Route::get('bills/{id}',               'show')->name('admin.bills.show');
            Route::match(['put', 'patch'], 'bills/{id}', 'update')->name('admin.bills.update');
            Route::delete('bills/{id}',            'destroy')->name('admin.bills.destroy');
            Route::post('generate-recurring-bills', 'generateRecurringBills')->name('admin.bills.generate-recurring');

            // Property / resident scopes
            Route::get('properties/{propertyId}/bills', 'propertyBills')->name('admin.properties.bills');
            Route::get('residents/{residentId}/bills',  'residentBills')->name('admin.residents.bills');
        });

        // Payments
        Route::controller(PaymentController::class)->group(function () {
            Route::get('payments',                 'index')->name('admin.payments.index');
            Route::post('payments',                'store')->name('admin.payments.store');
            Route::get('payments/{id}',            'show')->name('admin.payments.show');
            Route::match(['put', 'patch'], 'payments/{id}', 'update')->name('admin.payments.update');

            Route::get('bills/{billId}/payments',      'billPayments')->name('admin.bills.payments');
            Route::get('residents/{residentId}/payments', 'residentPayments')->name('admin.residents.payments');
        });

        // Payment methods
        Route::controller(PaymentMethodController::class)->group(function () {
            Route::get('payment-methods',               'index')->name('admin.payment-methods.index');
            Route::post('payment-methods',              'store')->name('admin.payment-methods.store');
            Route::get('payment-methods/{id}',          'show')->name('admin.payment-methods.show');
            Route::match(['put', 'patch'], 'payment-methods/{id}', 'update')
                ->name('admin.payment-methods.update');
            Route::delete('payment-methods/{id}',       'destroy')->name('admin.payment-methods.destroy');

            Route::get('residents/{residentId}/payment-methods', 'residentPaymentMethods')
                ->name('admin.residents.payment-methods');
            Route::post('payment-methods/{id}/set-default', 'setDefault')
                ->name('admin.payment-methods.set-default');
        });
    });

/*
|--------------------------------------------------------------------------
| Super-admin only
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'admin:super_admin'])
    ->post('admin/register', [AdminAuthController::class, 'register'])
    ->name('admin.register');

/*
|--------------------------------------------------------------------------
| Resident routes
|--------------------------------------------------------------------------
*/
Route::prefix('resident')
    ->middleware('auth:sanctum')
    ->group(function () {

        // Profile
        Route::get('profile', [ResidentAuthController::class, 'profile'])
            ->name('resident.profile');

        // Bills
        Route::controller(BillController::class)->group(function () {
            Route::get('bills',      'index')->name('resident.bills.index');
            Route::get('bills/{id}', 'show')->name('resident.bills.show');
        });

        // Payments
        Route::controller(PaymentController::class)->group(function () {
            Route::post('payments',  'store')->name('resident.payments.store');
            Route::get('payments',   'index')->name('resident.payments.index');
            Route::get('payments/{id}', 'show')->name('resident.payments.show');
        });

        // Payment methods
        Route::controller(PaymentMethodController::class)->group(function () {
            Route::get('payment-methods',               'residentPaymentMethods')
                ->middleware('substitute_auth_id')
                ->name('resident.payment-methods.index');

            Route::post('payment-methods',              'store')->name('resident.payment-methods.store');
            Route::get('payment-methods/{id}',          'show')->name('resident.payment-methods.show');
            Route::match(['put', 'patch'], 'payment-methods/{id}', 'update')
                ->name('resident.payment-methods.update');
            Route::delete('payment-methods/{id}',       'destroy')->name('resident.payment-methods.delete');
            Route::post('payment-methods/{id}/set-default', 'setDefault')
                ->name('resident.payment-methods.set-default');
        });
    });
