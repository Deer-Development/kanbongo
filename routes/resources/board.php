<?php

use App\Http\Controllers\Board\Destroy;
use App\Http\Controllers\Board\Index;
use App\Http\Controllers\Board\Show;
use App\Http\Controllers\Board\StateUpdate;
use App\Http\Controllers\Board\Store;
use App\Http\Controllers\Board\Update;
use App\Http\Controllers\Container\PaymentDetails;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'board'], function () {
    Route::get('/', Index::class)->name("board.index");
    Route::post('/', Store::class)->name("board.store");
    Route::get('/{id}', Show::class)->name("board.show");
    Route::get('/payment-details/{id}', PaymentDetails::class)->name("board.payment-details");
    Route::put('/{id}', Update::class)->name("board.update");
    Route::put('/state-update/{id}', StateUpdate::class)->name("board.state.update");
    Route::delete('/{id}', Destroy::class)->name("board.destroy");
});
