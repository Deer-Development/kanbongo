<?php

use App\Http\Controllers\Container\MemberPaymentDetails;
use App\Http\Controllers\Container\MemberPaychecksDetails;
use App\Http\Controllers\Container\ProcessPayment;
use App\Http\Controllers\Container\StateUpdate;
use App\Http\Controllers\Container\Tags;
use App\Http\Controllers\Container\Users;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Container\Index;
use App\Http\Controllers\Container\Store;
use App\Http\Controllers\Container\Show;
use App\Http\Controllers\Container\Update;
use App\Http\Controllers\Container\Destroy;

Route::group(['prefix' => 'container'], function () {
    Route::get('/', Index::class)->name("container.index");
    Route::post('/', Store::class)->name("container.store");
    Route::post('/{id}', Show::class)->name("container.show");
    Route::put('/{id}', Update::class)->name("container.update");
    Route::get('/tags/{id}', Tags::class)->name("container.fetch-tags");
    Route::get('/members/{id}', Users::class)->name("container.fetch-users");
    Route::put('/boards-state-update/{id}', StateUpdate::class)->name("container.boards-state-update");
    Route::get('/{id}/member-payment-details/{userId}', MemberPaymentDetails::class)->name("container.member-payment-details");
    Route::get('/{id}/member-paychecks-details/{userId}', MemberPaychecksDetails::class)->name("container.member-paychecks-details");
    Route::post('/{id}/process-payment/{userId}', ProcessPayment::class)->name("container.process-payment");
    Route::delete('/{id}', Destroy::class)->name("container.destroy");
});
