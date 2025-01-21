<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Project\Index;
use App\Http\Controllers\Project\Store;
use App\Http\Controllers\Project\Show;
use App\Http\Controllers\Project\Update;
use App\Http\Controllers\Project\Destroy;

Route::group(['prefix' => 'project'], function () {
    Route::get('/', Index::class)->name("project.index");
    Route::post('/', Store::class)->name("project.store");
    Route::get('/{id}', Show::class)->name("project.show");
    Route::put('/{id}', Update::class)->name("project.update");
    Route::delete('/{id}', Destroy::class)->name("project.destroy");
});
