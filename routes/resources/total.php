<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Total\Index;
use App\Http\Controllers\Total\Store;
use App\Http\Controllers\Total\Show;
use App\Http\Controllers\Total\Update;
use App\Http\Controllers\Total\Destroy;

Route::group(['prefix' => 'total'], function () {
    Route::get('/', Index::class)->name("total.index");
    Route::post('/', Store::class)->name("total.store");
    Route::get('/{id}', Show::class)->name("total.show");
    Route::put('/{id}', Update::class)->name("total.update");
    Route::delete('/{id}', Destroy::class)->name("total.destroy");
});
