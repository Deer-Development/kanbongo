<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RefreshTokenController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\General\Statuses;
use App\Http\Controllers\FileUploadController;
use Illuminate\Support\Facades\Route;

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
});
