<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function authUser(Request $request): UserResource
    {
        return new UserResource($request->user(), 'auth');
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }
}
