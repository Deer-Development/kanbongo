<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\Index;
use App\Http\Controllers\User\Store;
use App\Http\Controllers\User\Show;
use App\Http\Controllers\User\Options;
use App\Http\Controllers\User\Update;
use App\Http\Controllers\User\Destroy;

Route::group(['prefix' => 'user'], function () {
    Route::get('/', Index::class)->name("user.index");
    Route::get('/options', Options::class)->name("user.options");
    Route::post('/', Store::class)->name("user.store");
    Route::get('/{id}', Show::class)->name("user.show");
    Route::put('/{id}', Update::class)->name("user.update");
    Route::delete('/{id}', Destroy::class)->name("user.destroy");
});
