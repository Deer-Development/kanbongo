<?php

use App\Http\Controllers\Task\ToggleTimer;
use App\Http\Controllers\Task\UpdateTimers;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Task\Index;
use App\Http\Controllers\Task\Store;
use App\Http\Controllers\Task\Show;
use App\Http\Controllers\Task\Update;
use App\Http\Controllers\Task\Destroy;

Route::group(['prefix' => 'task'], function () {
    Route::get('/', Index::class)->name("task.index");
    Route::post('/', Store::class)->name("task.store");
    Route::get('/{id}', Show::class)->name("task.show");
    Route::put('/{id}', Update::class)->name("task.update");
    Route::post('/toggle-timer/{id}', ToggleTimer::class)->name("task.toggle-timer");
    Route::post('/update-timer/{id}', UpdateTimers::class)->name("task.change-timer");
    Route::delete('/{id}', Destroy::class)->name("task.destroy");
});
