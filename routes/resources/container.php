<?php

use App\Http\Controllers\Container\ProcessPayment;
use App\Http\Controllers\Container\StateUpdate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Container\Index;
use App\Http\Controllers\Container\Store;
use App\Http\Controllers\Container\Show;
use App\Http\Controllers\Container\Update;
use App\Http\Controllers\Container\Destroy;

Route::group(['prefix' => 'container'], function () {
    Route::get('/', Index::class)->name("container.index");
    Route::post('/', Store::class)->name("container.store");
    Route::get('/{id}', Show::class)->name("container.show");
    Route::put('/{id}', Update::class)->name("container.update");
    Route::put('/boards-state-update/{id}', StateUpdate::class)->name("container.boards-state-update");
    Route::post('/{id}/process-payment/{userId}', ProcessPayment::class)->name("container.process-payment");
    Route::delete('/{id}', Destroy::class)->name("container.destroy");
});
