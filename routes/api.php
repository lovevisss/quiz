<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->get('/auth/user', [UserController::class, 'authUser'])->name('api.auth.user');

Route::middleware('auth:api')->group(function (): void {
    Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
    Route::get('/roles', [RoleController::class, 'index'])->name('api.roles.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('api.users.show');
    Route::post('/users/{user}/roles', [RoleController::class, 'assignToUser'])
        ->middleware('role:admin')
        ->name('api.users.roles.assign');
    Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'removeFromUser'])
        ->middleware('role:admin')
        ->name('api.users.roles.remove');
});
