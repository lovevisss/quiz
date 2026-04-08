<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => RoleResource::collection($roles)->resolve(),
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function assignToUser(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user->assignRole($validated['role']);

        $roles = $user->roles()->orderBy('id')->get();

        return response()->json([
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => [
                    'roles' => RoleResource::collection($roles)->resolve(),
                ],
            ],
            'links' => [
                'self' => url('/api/users/'.$user->id.'/roles'),
            ],
        ]);
    }

    public function removeFromUser(User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);

        $roles = $user->roles()->orderBy('id')->get();

        return response()->json([
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => [
                    'roles' => RoleResource::collection($roles)->resolve(),
                ],
            ],
            'links' => [
                'self' => url('/api/users/'.$user->id.'/roles'),
            ],
        ]);
    }
}
