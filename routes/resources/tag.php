<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tag\Index;
use App\Http\Controllers\Tag\Store;
use App\Http\Controllers\Tag\Show;
use App\Http\Controllers\Tag\Update;
use App\Http\Controllers\Tag\Destroy;

Route::group(['prefix' => 'tag'], function () {
    Route::get('/', Index::class)->name("tag.index");
    Route::post('/', Store::class)->name("tag.store");
    Route::get('/{id}', Show::class)->name("tag.show");
    Route::put('/{id}', Update::class)->name("tag.update");
    Route::delete('/{id}', Destroy::class)->name("tag.destroy");
});
