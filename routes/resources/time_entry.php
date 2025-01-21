<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TimeEntry\Index;
use App\Http\Controllers\TimeEntry\Store;
use App\Http\Controllers\TimeEntry\Show;
use App\Http\Controllers\TimeEntry\Update;
use App\Http\Controllers\TimeEntry\Destroy;

Route::group(['prefix' => 'time-entry'], function () {
    Route::get('/', Index::class)->name("time_entry.index");
    Route::post('/', Store::class)->name("time_entry.store");
    Route::get('/{id}', Show::class)->name("time_entry.show");
    Route::put('/{id}', Update::class)->name("time_entry.update");
    Route::delete('/{id}', Destroy::class)->name("time_entry.destroy");
});
