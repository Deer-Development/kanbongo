<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\General\Statuses;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\DocumentationCommentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\User\UpdateProfile;
use App\Http\Controllers\User\UpdateEmail;
use App\Http\Controllers\User\NotificationPreferencesController;
use App\Http\Controllers\User\UpdateNotificationPreferences;
use App\Http\Controllers\User\PaymentDetailsController;
use App\Http\Resources\User\UserResource;
use App\Http\Controllers\Container\PaymentController;

Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::group(['prefix' => 'auth'], function () {
    Route::post('/send-token', [LoginController::class, 'sendLoginToken'])->name('vue.user.send-token');
    Route::post('/register', [RegisterController::class, 'register'])->name('vue.user.register');
    Route::post('/verify-token', [LoginController::class, 'verifyLoginToken'])->name('vue.user.verify-token');
    Route::post('forgot-password', ForgotPasswordController::class)->name('vue.user.forgot-password');
    Route::post('reset-password', ResetPasswordController::class)->name('vue.user.reset-password');
    Route::post('/login', [LoginController::class, 'sendLoginToken'])->name('vue.user.login');

    Route::group(['middleware' => 'auth:sanctum'], function() {
        Route::post('logout', LogoutController::class)->name('vue.user.logout');
        Route::post('/update-details', [LoginController::class, 'updateDetails'])->name('vue.user.update-details');
        Route::post('refresh', RefreshTokenController::class)->name('vue.user.refresh');
    });
});

Route::group(['middleware' => 'auth:sanctum'], function() {
    Route::post('/upload-temp-spatie', [FileUploadController::class, 'uploadTempSpatie']);
    Route::delete('/delete-temp-spatie/{media}', [FileUploadController::class, 'deleteTempSpatie']);

    Route::post('/user/profile', UpdateProfile::class)->name('user.profile');
    Route::post('/user/email/send-token', [UpdateEmail::class, 'sendToken'])->name('user.email.sendToken');
    Route::post('/user/email/verify-token', [UpdateEmail::class, 'verifyToken'])->name('user.email.verifyToken');
    Route::post('/user/notification-preferences', UpdateNotificationPreferences::class)->name('user.notification-preferences.update');
    Route::get('/user/notification-preferences', [NotificationPreferencesController::class, 'index'])->name('user.notification-preferences.index');
    Route::get('/user/payment-details', [PaymentDetailsController::class, 'show'])->name('user.payment-details');
    Route::post('/user/payment-details', [PaymentDetailsController::class, 'store'])->name('user.payment-details.store');

    require base_path('routes/resources/user.php');
    require base_path('routes/resources/project.php');
    require base_path('routes/resources/container.php');
    require base_path('routes/resources/board.php');
    require base_path('routes/resources/task.php');
    require base_path('routes/resources/tag.php');
    require base_path('routes/resources/member.php');
    require base_path('routes/resources/comment.php');
    require base_path('routes/resources/time_entry.php');
    require base_path('routes/resources/total.php');

    Route::group(['prefix' => 'general'], function() {
       Route::get('statuses/priority', [Statuses::class, 'priority'])->name('statuses.priority');
    });

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::patch('/notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::patch('/notifications/{notification}/mark-as-unread', [NotificationController::class, 'markAsUnread']);

    Route::get('/container/{container}/documentation-tabs', [DocumentationController::class, 'index']);
    Route::post('/container/{container}/documentation-tab', [DocumentationController::class, 'store']);
    Route::get('/container/documentation-tab/{tab}', [DocumentationController::class, 'show']);
    Route::put('/container/documentation-tab/{tab}', [DocumentationController::class, 'update']);
    Route::delete('/container/documentation-tab/{tab}', [DocumentationController::class, 'destroy']);
    Route::put('/container/documentation-tabs/order', [DocumentationController::class, 'updateOrder']);

    Route::prefix('container')->group(function () {
        Route::post('documentation-tab/{tab}/version', [DocumentationController::class, 'createVersion']);
        Route::get('documentation-tab/{tab}/versions', [DocumentationController::class, 'getVersions']);
        Route::post('documentation-tab/{tab}/version/{version}/restore', [DocumentationController::class, 'restoreVersion']);
        Route::get('documentation-tab/{tab}/comments', [DocumentationCommentController::class, 'index']);
        Route::post('documentation-tab/{tab}/comment', [DocumentationCommentController::class, 'store']);
        Route::put('documentation-comment/{comment}', [DocumentationCommentController::class, 'update']);
        Route::delete('documentation-comment/{comment}', [DocumentationCommentController::class, 'destroy']);
        Route::post('documentation-comment/{comment}/resolve', [DocumentationCommentController::class, 'resolve']);
        Route::post('documentation-comment/{comment}/unresolve', [DocumentationCommentController::class, 'unresolve']);
    });

    Route::get('/container/{container}/documentation-search', [DocumentationController::class, 'search']);
});

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/auth/me', function () {
        return new UserResource(auth()->user());
    });
    Route::post('/container/{boardId}/process-payment/{userId}', [PaymentController::class, 'processPayment']);
    Route::get('/payments/{transferId}/status', [PaymentController::class, 'getPaymentStatus']);
});
