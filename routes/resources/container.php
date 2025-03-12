<?php

use App\Http\Controllers\Container\MemberPaymentDetails;
use App\Http\Controllers\Container\MemberPaychecksDetails;
use App\Http\Controllers\Container\ProcessPayment;
use App\Http\Controllers\Container\StateUpdate;
use App\Http\Controllers\Container\Tags;
use App\Http\Controllers\Container\Activities;
use App\Http\Controllers\Container\Users;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Container\Index;
use App\Http\Controllers\Container\Store;
use App\Http\Controllers\Container\Show;
use App\Http\Controllers\Container\Update;
use App\Http\Controllers\Container\Destroy;
use App\Http\Controllers\Container\ShowForComments;
use App\Http\Controllers\Container\Move;
use App\Http\Controllers\Container\ContainerActivities;
use App\Http\Controllers\Container\TransferOwnership;

Route::group(['prefix' => 'container'], function () {
    Route::get('/', Index::class)->name("container.index");
    Route::post('/', Store::class)->name("container.store");
    Route::post('/{id}', Show::class)->name("container.show");
    Route::get('/{id}/comments', ShowForComments::class)->name("container.show-for-comments");
    Route::get('/{id}/board-activities', ContainerActivities::class)->name("container.board-activities");
    Route::put('/{id}', Update::class)->name("container.update");
    Route::post('/{id}/move', Move::class)->name("container.move");
    Route::get('/tags/{id}', Tags::class)->name("container.fetch-tags");
    Route::get('/members/{id}', Users::class)->name("container.fetch-users");
    Route::post('/activities/{id}', Activities::class)->name("container.fetch-activities");
    Route::put('/boards-state-update/{id}', StateUpdate::class)->name("container.boards-state-update");
    Route::get('/{id}/member-payment-details/{userId}', MemberPaymentDetails::class)->name("container.member-payment-details");
    Route::get('/{id}/member-paychecks-details/{userId}', MemberPaychecksDetails::class)->name("container.member-paychecks-details");
    Route::post('/{id}/process-payment/{userId}', ProcessPayment::class)->name("container.process-payment");
    Route::post('/{id}/mark-as-paid/{userId}', [ProcessPayment::class, 'markAsPaid'])->name("container.mark-as-paid");
    Route::delete('/{id}', Destroy::class)->name("container.destroy");
    Route::post('/{id}/transfer-ownership', TransferOwnership::class)->name("container.transfer-ownership");
});
