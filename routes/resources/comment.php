<?php

use App\Http\Controllers\Comment\MarkAsRead;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Comment\Index;
use App\Http\Controllers\Comment\Store;
use App\Http\Controllers\Comment\Show;
use App\Http\Controllers\Comment\Update;
use App\Http\Controllers\Comment\Destroy;

Route::group(['prefix' => 'comment'], function () {
    Route::get('/', Index::class)->name("comment.index");
    Route::post('/', Store::class)->name("comment.store");
    Route::post('/mark-as-read', MarkAsRead::class)->name("comment.mark-as-read");
    Route::get('/{id}', Show::class)->name("comment.show");
    Route::put('/{id}', Update::class)->name("comment.update");
    Route::delete('/{id}', Destroy::class)->name("comment.destroy");
});
