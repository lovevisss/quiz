<?php

use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::inertia('/index', 'Index')->name('index');
Route::inertia('/friends', 'Index')->name('friends');
Route::inertia('/watch', 'Index')->name('watch');
Route::get('/apis/posts', [PostController::class, 'legacyIndex'])->name('apis.posts.index');
Route::post('api/friend-requests', [\App\Http\Controllers\Api\FriendRequestController::class, 'store'])->name('api.friend-requests.store');
Route::post('api/friend-requests/{user}/accept', [\App\Http\Controllers\Api\FriendRequestController::class, 'accept'])->name('api.friend-requests.accept');
require __DIR__.'/settings.php';
