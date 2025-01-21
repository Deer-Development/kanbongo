<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Member\Index;
use App\Http\Controllers\Member\Store;
use App\Http\Controllers\Member\Show;
use App\Http\Controllers\Member\Update;
use App\Http\Controllers\Member\Destroy;

Route::group(['prefix' => 'member'], function () {
    Route::get('/', Index::class)->name("member.index");
    Route::post('/', Store::class)->name("member.store");
    Route::get('/{id}', Show::class)->name("member.show");
    Route::put('/{id}', Update::class)->name("member.update");
    Route::delete('/{id}', Destroy::class)->name("member.destroy");
});
