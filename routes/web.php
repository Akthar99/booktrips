<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPackageController;
use App\Http\Controllers\Admin\AdminPartnerController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailChangeController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\Partner\PartnerAnalyticsController;
use App\Http\Controllers\Partner\PartnerBookingController;
use App\Http\Controllers\Partner\PartnerDashboardController;
use App\Http\Controllers\Partner\PartnerImageController;
use App\Http\Controllers\Partner\PartnerPackageController;
use App\Http\Controllers\Partner\PartnerPaymentController;
use App\Http\Controllers\Partner\PartnerPhoneVerificationController;
use App\Http\Controllers\Partner\PartnerProfileController;
use App\Http\Controllers\Partner\PartnerRegistrationController;
use App\Http\Controllers\ReceiptDownloadController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [PackageController::class, 'index'])->name('search');
Route::get('/map', [PackageController::class, 'map'])->name('map');
Route::get('/packages/{package}', [PackageController::class, 'show'])->name('packages.show');
Route::get('/packages/{package}/quote', [PackageController::class, 'quote'])
    ->middleware('throttle:60,1')
    ->name('packages.quote');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/partners', [PageController::class, 'partners'])->name('partners');
Route::get('/geo/search', [GeoController::class, 'search'])
    ->middleware('throttle:30,1')
    ->name('geo.search');

/*
|--------------------------------------------------------------------------
| Guest auth
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:20,1');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.store');
});

/*
|--------------------------------------------------------------------------
| Partner applications (guests create an owner account, travellers upgrade)
|--------------------------------------------------------------------------
*/

Route::get('/partners/apply', [PartnerRegistrationController::class, 'create'])->name('partner.apply');
Route::post('/partners/apply', [PartnerRegistrationController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('partner.register');

// Mobile verification for partner applications (SMS one-time codes).
Route::post('/partners/apply/phone', [PartnerPhoneVerificationController::class, 'send'])
    ->middleware('throttle:partner-otp')
    ->name('partner.phone.send');
Route::post('/partners/apply/phone/confirm', [PartnerPhoneVerificationController::class, 'confirm'])
    ->middleware('throttle:partner-otp-confirm')
    ->name('partner.phone.confirm');

Route::get('/partners/register', fn () => redirect()->route('partner.apply'))->name('partner.register.legacy');

/*
|--------------------------------------------------------------------------
| Authenticated
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Email verification
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    // Email change
    Route::post('/account/email', [EmailChangeController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('account.email');
    Route::get('/email/change/{user}', [EmailChangeController::class, 'confirm'])
        ->middleware('signed')
        ->name('email.change.confirm');

    // Account
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::put('/account/profile', [AccountController::class, 'update'])->name('account.profile');
    Route::post('/account/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('account.password');

    // Notifications
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // Private receipt downloads (owner partner or admin)
    Route::get('/receipts/{receipt}', ReceiptDownloadController::class)->name('receipts.download');

    // Traveller bookings
    Route::get('/account/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/account/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    Route::get('/book/{package}', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])
        ->middleware(['verified', 'throttle:20,1'])
        ->name('bookings.store');

    // Pending partner holding page
    Route::get('/partners/pending', [PartnerRegistrationController::class, 'pending'])->name('partner.pending');
});

/*
|--------------------------------------------------------------------------
| Partner dashboard (approved partners only)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'partner'])
    ->prefix('partners')
    ->name('partner.')
    ->group(function (): void {
        Route::get('/dashboard', [PartnerDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profile', [PartnerProfileController::class, 'update'])->name('profile.update');

        Route::get('/packages/new', [PartnerPackageController::class, 'create'])->name('packages.create');
        Route::post('/packages', [PartnerPackageController::class, 'store'])->name('packages.store');
        Route::get('/packages/{package}/edit', [PartnerPackageController::class, 'edit'])->name('packages.edit');
        Route::put('/packages/{package}', [PartnerPackageController::class, 'update'])->name('packages.update');
        Route::patch('/packages/{package}/publish', [PartnerPackageController::class, 'publish'])->name('packages.publish');
        Route::delete('/packages/{package}', [PartnerPackageController::class, 'destroy'])->name('packages.destroy');
        Route::post('/images', [PartnerImageController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('images.store');

        Route::get('/bookings', [PartnerBookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [PartnerBookingController::class, 'show'])->name('bookings.show');
        Route::patch('/bookings/{booking}/status', [PartnerBookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('/payments', [PartnerPaymentController::class, 'index'])->name('payments.index');
        Route::post('/payments/receipts', [PartnerPaymentController::class, 'store'])
            ->middleware('throttle:20,1')
            ->name('payments.receipts.store');

        Route::get('/analytics', [PartnerAnalyticsController::class, 'index'])->name('analytics.index');
    });

/*
|--------------------------------------------------------------------------
| Super admin console
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('overview');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users');
        Route::patch('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::get('/partners', [AdminPartnerController::class, 'index'])->name('partners');
        Route::patch('/partners/{business}/approve', [AdminPartnerController::class, 'approve'])->name('partners.approve');

        Route::get('/listings', [AdminPackageController::class, 'index'])->name('listings');
        Route::patch('/packages/{package}', [AdminPackageController::class, 'update'])->name('packages.update');

        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings');
        Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments');
        Route::patch('/receipts/{receipt}', [AdminPaymentController::class, 'updateReceipt'])->name('receipts.update');

        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews');
    });
