<?php

use App\Http\Controllers\Api\Admin\AdminDeliveryController;
use App\Http\Controllers\Api\Admin\AdminJobApplicationController;
use App\Http\Controllers\Api\Admin\AdminJobOfferController;
use App\Http\Controllers\Api\Admin\AdminJobSeekerController;
use App\Http\Controllers\Api\Admin\AdminOrderController;
use App\Http\Controllers\Api\Admin\AdminPaymentController;
use App\Http\Controllers\Api\Admin\AdminProductController;
use App\Http\Controllers\Api\Admin\AdminPropertyController;
use App\Http\Controllers\Api\Admin\AdminPropertyOwnerController;
use App\Http\Controllers\Api\Admin\AdminRatingController;
use App\Http\Controllers\Api\Admin\AdminRecruiterController;
use App\Http\Controllers\Api\Admin\AdminReservationController;
use App\Http\Controllers\Api\Admin\AdminStatsController;
use App\Http\Controllers\Api\Admin\AdminSubscriptionController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\Admin\AdminVendorController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverDeliveryController;
use App\Http\Controllers\Api\JobApplicationController;
use App\Http\Controllers\Api\JobOfferController;
use App\Http\Controllers\Api\JobSeekerController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\OtpController;
use App\Http\Controllers\Api\OwnerPropertyController;
use App\Http\Controllers\Api\OwnerReservationController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\PropertyOwnerController;
use App\Http\Controllers\Api\RatingController;
use App\Http\Controllers\Api\RecruiterApplicationController;
use App\Http\Controllers\Api\RecruiterController;
use App\Http\Controllers\Api\RecruiterJobOfferController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\VendorOrderController;
use App\Http\Controllers\Api\VendorProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Vérification du numéro par OTP SMS (AfrikSMS), avant inscription.
// Le throttle par IP double la limitation par numéro appliquée dans OtpService.
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/auth/otp/send', [OtpController::class, 'send']);
    Route::post('/auth/otp/verify', [OtpController::class, 'verify']);
});

// Webhook de confirmation PayGate Global (non authentifié, re-vérifié côté serveur).
Route::post('/payments/callback', [PaymentController::class, 'callback']);

/*
|--------------------------------------------------------------------------
| Authenticated routes (Sanctum token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Vérification par OTP SMS du numéro déjà enregistré sur le compte.
    Route::post('/otp/send', [OtpController::class, 'sendForMe'])->middleware('throttle:10,1');
    Route::post('/otp/verify', [OtpController::class, 'verifyForMe'])->middleware('throttle:10,1');

    // Vendor profile of the authenticated user.
    Route::get('/vendor', [VendorController::class, 'show']);
    Route::post('/vendor', [VendorController::class, 'store']);
    Route::put('/vendor', [VendorController::class, 'update']);

    // Driver profile of the authenticated user.
    Route::get('/driver', [DriverController::class, 'show']);
    Route::post('/driver', [DriverController::class, 'store']);
    Route::put('/driver', [DriverController::class, 'update']);

    // Property-owner profile of the authenticated user.
    Route::get('/property-owner', [PropertyOwnerController::class, 'show']);
    Route::post('/property-owner', [PropertyOwnerController::class, 'store']);
    Route::put('/property-owner', [PropertyOwnerController::class, 'update']);

    // Recruiter profile of the authenticated user.
    Route::get('/recruiter', [RecruiterController::class, 'show']);
    Route::post('/recruiter', [RecruiterController::class, 'store']);
    Route::put('/recruiter', [RecruiterController::class, 'update']);

    // Job-seeker profile of the authenticated user.
    Route::get('/job-seeker', [JobSeekerController::class, 'show']);
    Route::post('/job-seeker', [JobSeekerController::class, 'store']);
    Route::put('/job-seeker', [JobSeekerController::class, 'update']);

    // Public product catalogue (browse/search) — any authenticated user.
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);

    // Product management — scoped to the authenticated vendor's own products.
    Route::get('/vendor/products', [VendorProductController::class, 'index']);
    Route::post('/vendor/products', [VendorProductController::class, 'store']);
    Route::get('/vendor/products/{product}', [VendorProductController::class, 'show']);
    Route::put('/vendor/products/{product}', [VendorProductController::class, 'update']);
    Route::delete('/vendor/products/{product}', [VendorProductController::class, 'destroy']);

    // Orders placed by the authenticated customer.
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/confirm-receipt', [OrderController::class, 'confirmReceipt']);

    // Orders received by the authenticated vendor.
    Route::get('/vendor/orders', [VendorOrderController::class, 'index']);
    Route::get('/vendor/orders/{order}', [VendorOrderController::class, 'show']);
    Route::patch('/vendor/orders/{order}/status', [VendorOrderController::class, 'updateStatus']);

    // Deliveries handled by the authenticated driver.
    Route::get('/driver/deliveries/available', [DriverDeliveryController::class, 'available']);
    Route::get('/driver/deliveries', [DriverDeliveryController::class, 'index']);
    Route::post('/driver/deliveries/{delivery}/claim', [DriverDeliveryController::class, 'claim']);
    Route::post('/driver/deliveries/{delivery}/pickup', [DriverDeliveryController::class, 'pickup']);

    // Public property catalogue (browse/search) — any authenticated user.
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{property}', [PropertyController::class, 'show']);

    // Property management — scoped to the authenticated owner's own properties.
    Route::get('/property-owner/properties', [OwnerPropertyController::class, 'index']);
    Route::post('/property-owner/properties', [OwnerPropertyController::class, 'store']);
    Route::get('/property-owner/properties/{property}', [OwnerPropertyController::class, 'show']);
    Route::put('/property-owner/properties/{property}', [OwnerPropertyController::class, 'update']);
    Route::delete('/property-owner/properties/{property}', [OwnerPropertyController::class, 'destroy']);

    // Reservations made by the authenticated customer.
    Route::get('/reservations', [ReservationController::class, 'index']);
    Route::post('/reservations', [ReservationController::class, 'store']);
    Route::get('/reservations/{reservation}', [ReservationController::class, 'show']);
    Route::post('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel']);

    // Reservations received by the authenticated property owner.
    Route::get('/property-owner/reservations', [OwnerReservationController::class, 'index']);
    Route::patch('/property-owner/reservations/{reservation}/status', [OwnerReservationController::class, 'updateStatus']);

    // Public job board (browse/search) — any authenticated user.
    Route::get('/job-offers', [JobOfferController::class, 'index']);
    Route::get('/job-offers/{jobOffer}', [JobOfferController::class, 'show']);

    // Job-offer management — scoped to the authenticated recruiter's own offers.
    Route::get('/recruiter/job-offers', [RecruiterJobOfferController::class, 'index']);
    Route::post('/recruiter/job-offers', [RecruiterJobOfferController::class, 'store']);
    Route::get('/recruiter/job-offers/{jobOffer}', [RecruiterJobOfferController::class, 'show']);
    Route::put('/recruiter/job-offers/{jobOffer}', [RecruiterJobOfferController::class, 'update']);
    Route::delete('/recruiter/job-offers/{jobOffer}', [RecruiterJobOfferController::class, 'destroy']);

    // Job applications submitted by the authenticated job seeker.
    Route::post('/job-offers/{jobOffer}/apply', [JobApplicationController::class, 'apply']);
    Route::get('/job-seeker/applications', [JobApplicationController::class, 'index']);
    Route::post('/job-seeker/applications/{application}/withdraw', [JobApplicationController::class, 'withdraw']);

    // Applications received by the authenticated recruiter.
    Route::get('/recruiter/job-offers/{jobOffer}/applications', [RecruiterApplicationController::class, 'index']);
    Route::patch('/recruiter/applications/{application}/status', [RecruiterApplicationController::class, 'updateStatus']);

    // Notifications of the authenticated user (fed by every module).
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

    // Direct messaging between users.
    Route::get('/conversations', [MessageController::class, 'conversations']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount']);
    Route::get('/messages/{user}', [MessageController::class, 'thread']);

    // Ratings of business profiles.
    Route::post('/ratings', [RatingController::class, 'store']);
    Route::get('/my-ratings', [RatingController::class, 'mine']);
    Route::get('/ratings/{targetType}/{targetId}', [RatingController::class, 'forTarget']);

    // Payments (PayGate Global — Flooz / TMoney) for orders, reservations and subscriptions.
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    Route::post('/payments/{payment}/check', [PaymentController::class, 'check']);

    /*
    |--------------------------------------------------------------------------
    | Admin back-office (user_type = admin only)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Dashboard KPIs.
        Route::get('/stats', [AdminStatsController::class, 'index']);

        // User management.
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::patch('/users/{user}/status', [AdminUserController::class, 'updateStatus']);

        // Commerce oversight — vendors.
        Route::get('/vendors', [AdminVendorController::class, 'index']);
        Route::get('/vendors/{vendor}', [AdminVendorController::class, 'show']);
        Route::patch('/vendors/{vendor}/status', [AdminVendorController::class, 'updateStatus']);

        // Commerce oversight — products (catalogue moderation).
        Route::get('/products', [AdminProductController::class, 'index']);
        Route::get('/products/{product}', [AdminProductController::class, 'show']);
        Route::patch('/products/{product}/availability', [AdminProductController::class, 'updateAvailability']);

        // Commerce oversight — orders (read-only).
        Route::get('/orders', [AdminOrderController::class, 'index']);
        Route::get('/orders/{order}', [AdminOrderController::class, 'show']);

        // Commerce oversight — deliveries (read-only).
        Route::get('/deliveries', [AdminDeliveryController::class, 'index']);
        Route::get('/deliveries/{delivery}', [AdminDeliveryController::class, 'show']);

        // Real-estate oversight — property owners.
        Route::get('/property-owners', [AdminPropertyOwnerController::class, 'index']);
        Route::get('/property-owners/{owner}', [AdminPropertyOwnerController::class, 'show']);

        // Real-estate oversight — properties (listings moderation).
        Route::get('/properties', [AdminPropertyController::class, 'index']);
        Route::get('/properties/{property}', [AdminPropertyController::class, 'show']);
        Route::patch('/properties/{property}/availability', [AdminPropertyController::class, 'updateAvailability']);

        // Real-estate oversight — reservations (read-only).
        Route::get('/reservations', [AdminReservationController::class, 'index']);
        Route::get('/reservations/{reservation}', [AdminReservationController::class, 'show']);

        // Jobs oversight — recruiters.
        Route::get('/recruiters', [AdminRecruiterController::class, 'index']);
        Route::get('/recruiters/{recruiter}', [AdminRecruiterController::class, 'show']);

        // Jobs oversight — job seekers.
        Route::get('/job-seekers', [AdminJobSeekerController::class, 'index']);
        Route::get('/job-seekers/{seeker}', [AdminJobSeekerController::class, 'show']);

        // Jobs oversight — job offers (board moderation).
        Route::get('/job-offers', [AdminJobOfferController::class, 'index']);
        Route::get('/job-offers/{jobOffer}', [AdminJobOfferController::class, 'show']);
        Route::patch('/job-offers/{jobOffer}/status', [AdminJobOfferController::class, 'updateStatus']);

        // Jobs oversight — applications (read-only).
        Route::get('/job-applications', [AdminJobApplicationController::class, 'index']);
        Route::get('/job-applications/{application}', [AdminJobApplicationController::class, 'show']);

        // Cross-cutting — payments (read-only).
        Route::get('/payments', [AdminPaymentController::class, 'index']);
        Route::get('/payments/{payment}', [AdminPaymentController::class, 'show']);

        // Cross-cutting — ratings (moderation).
        Route::get('/ratings', [AdminRatingController::class, 'index']);
        Route::get('/ratings/{rating}', [AdminRatingController::class, 'show']);
        Route::delete('/ratings/{rating}', [AdminRatingController::class, 'destroy']);

        // Cross-cutting — subscription plans (CRUD).
        Route::get('/subscriptions', [AdminSubscriptionController::class, 'index']);
        Route::post('/subscriptions', [AdminSubscriptionController::class, 'store']);
        Route::get('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'show']);
        Route::put('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'update']);
        Route::delete('/subscriptions/{subscription}', [AdminSubscriptionController::class, 'destroy']);
    });
});
