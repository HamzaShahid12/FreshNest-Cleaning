<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\Staff\StaffController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/* ------------------------------ Public site ------------------------------ */
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services.index');
Route::get('/services/{service:slug}', [PageController::class, 'service'])->name('services.show');
Route::get('/pricing', [PageController::class, 'pricing'])->name('pricing');
Route::get('/gallery', [PageController::class, 'gallery'])->name('gallery');
Route::get('/reviews', [PageController::class, 'reviews'])->name('reviews');
Route::get('/areas', [PageController::class, 'areas'])->name('areas.index');
Route::get('/areas/{area:slug}', [PageController::class, 'area'])->name('areas.show');
Route::get('/api/check-area', [PageController::class, 'checkArea'])->middleware('throttle:30,1')->name('area.check');
Route::get('/api/check-zip', [PageController::class, 'checkZip'])->middleware('throttle:30,1')->name('zip.check');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::post('/newsletter', [PageController::class, 'newsletter'])->middleware('throttle:5,1')->name('newsletter');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/blog', [PageController::class, 'blog'])->name('blog.index');
Route::get('/blog/{post:slug}', [PageController::class, 'post'])->name('blog.show');
Route::get('/legal/{slug}', [PageController::class, 'legal'])->name('legal');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

/* ------------------------------ Booking wizard --------------------------- */
Route::get('/book', [BookingController::class, 'wizard'])->name('booking.wizard');
Route::post('/api/booking/quote', [BookingController::class, 'quote'])->middleware('throttle:60,1')->name('booking.quote');
Route::get('/api/booking/availability', [BookingController::class, 'availability'])->middleware('throttle:60,1')->name('booking.availability');
Route::post('/api/booking', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('booking.store');
Route::get('/book/confirmation/{booking}', [BookingController::class, 'confirmation'])->middleware('auth')->name('booking.confirmation');

/* ------------------------------ Stripe webhook --------------------------- */
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

/* --------------------------------- Auth ---------------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/staff/login', [AuthController::class, 'showStaffLogin'])->name('staff.login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', [AuthController::class, 'verifyNotice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/password/change', [AuthController::class, 'showForcePassword'])->name('password.force');
    Route::post('/password/change', [AuthController::class, 'forcePassword'])->name('password.force.update');
});

/* ------------------------------ Customer portal -------------------------- */
Route::middleware(['auth', 'force.password', 'role:customer'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [AccountController::class, 'bookings'])->name('bookings');
    Route::get('/bookings/{booking}', [AccountController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/slots', [AccountController::class, 'slots'])->name('bookings.slots');
    Route::post('/bookings/{booking}/cancel', [AccountController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/reschedule', [AccountController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('/bookings/{booking}/review', [AccountController::class, 'review'])->name('bookings.review');
    Route::get('/addresses', [AccountController::class, 'addresses'])->name('addresses');
    Route::post('/addresses', [AccountController::class, 'storeAddress'])->name('addresses.store');
    Route::put('/addresses/{address}', [AccountController::class, 'updateAddress'])->name('addresses.update');
    Route::delete('/addresses/{address}', [AccountController::class, 'destroyAddress'])->name('addresses.destroy');
    Route::get('/invoices', [AccountController::class, 'invoices'])->name('invoices');
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('password');
    Route::get('/support', [AccountController::class, 'support'])->name('support');
    Route::post('/support', [AccountController::class, 'sendSupport'])->middleware('throttle:5,1')->name('support.send');
});

/* ----------------------- Profile (every signed-in role) ------------------- */
Route::middleware(['auth', 'force.password'])->prefix('profile')->name('profile.')->group(function () {
    Route::put('/', [ProfileController::class, 'update'])->name('update');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    Route::post('/avatar', [ProfileController::class, 'avatar'])->middleware('throttle:20,1')->name('avatar');
    Route::delete('/avatar', [ProfileController::class, 'removeAvatar'])->name('avatar.remove');
});

/* ------------------------------ Employee portal -------------------------- */
Route::middleware(['auth', 'force.password', 'role:staff'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/', [StaffController::class, 'dashboard'])->name('dashboard');
    Route::get('/history', [StaffController::class, 'history'])->name('history');
    Route::get('/jobs/{booking}', [StaffController::class, 'show'])->name('jobs.show');
    Route::post('/jobs/{booking}/status', [StaffController::class, 'updateStatus'])->name('jobs.status');
    Route::post('/jobs/{booking}/notes', [StaffController::class, 'addNotes'])->name('jobs.notes');
    Route::get('/profile', [ProfileController::class, 'staff'])->name('profile');
});

/* ------------------------------ Admin panel ------------------------------ */
Route::middleware(['auth', 'force.password', 'role:super_admin,admin,manager'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/bookings', [Admin\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/print', [Admin\BookingController::class, 'print'])->name('bookings.print');
    Route::get('/bookings/{booking}/slots', [Admin\BookingController::class, 'slots'])->name('bookings.slots');
    Route::post('/bookings/{booking}/status', [Admin\BookingController::class, 'status'])->name('bookings.status');
    Route::post('/bookings/{booking}/assign', [Admin\BookingController::class, 'assign'])->name('bookings.assign');
    Route::post('/bookings/{booking}/reschedule', [Admin\BookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::post('/bookings/{booking}/notes', [Admin\BookingController::class, 'notes'])->name('bookings.notes');
    Route::post('/bookings/{booking}/payment', [Admin\BookingController::class, 'recordPayment'])->name('bookings.payment');
    Route::post('/bookings/{booking}/quote', [Admin\BookingController::class, 'setQuote'])->name('bookings.quote');
    Route::post('/bookings/{booking}/refund', [Admin\BookingController::class, 'refund'])->name('bookings.refund');

    Route::get('/profile', [ProfileController::class, 'admin'])->name('profile');
    Route::get('/calendar', [Admin\CalendarController::class, 'index'])->name('calendar');

    Route::resource('customers', Admin\CustomerController::class)->except(['destroy']);
    Route::resource('employees', Admin\EmployeeController::class)->except(['destroy']);

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export', [Admin\ReportController::class, 'export'])->name('reports.export');

    // Admin and Super Admin only (managers can still run day-to-day operations above).
    Route::middleware('role:super_admin,admin')->group(function () {
        Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
        Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit');
        Route::get('/branding', [Admin\BrandingController::class, 'index'])->name('branding');
        Route::post('/branding/{key}', [Admin\BrandingController::class, 'update'])->where('key', '[a-z_]+')->name('branding.update');
        Route::delete('/branding/{key}', [Admin\BrandingController::class, 'destroy'])->where('key', '[a-z_]+')->name('branding.reset');
    });

    // Generic CRUD modules (access per module is checked inside the controller).
    Route::prefix('m')->name('resource.')->group(function () {
        Route::get('/{resource}', [Admin\ResourceController::class, 'index'])->name('index');
        Route::get('/{resource}/create', [Admin\ResourceController::class, 'create'])->name('create');
        Route::post('/{resource}', [Admin\ResourceController::class, 'store'])->name('store');
        Route::get('/{resource}/{id}/edit', [Admin\ResourceController::class, 'edit'])->whereNumber('id')->name('edit');
        Route::put('/{resource}/{id}', [Admin\ResourceController::class, 'update'])->whereNumber('id')->name('update');
        Route::delete('/{resource}/{id}', [Admin\ResourceController::class, 'destroy'])->whereNumber('id')->name('destroy');
    });
});
