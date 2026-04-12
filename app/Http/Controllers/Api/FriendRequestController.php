<?php

namespace App\Http\Controllers\Api;

use App\Models\Friend;
use App\Models\User;

class FriendRequestController
{
    public function store()
    {
        $data = request()->validate([
            'friend_id' => ['required', 'integer'],
        ]);

        $friend = User::query()->find($data['friend_id']);

        if ($friend === null) {
            return response()->json([
                'errors' => [
                    'code' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Unable to fetch friend request.',
                ],
            ], 404);
        }

        $friendRequest = Friend::create([
            'friend_id' => $data['friend_id'],
            'user_id' => request()->user()->id,
            'status' => 0,
        ]);

        return response()->json([
            'data' => [
                'type' => 'friend_requests',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => null,
                ],
            ],
            'links' => [
                'self' => url('/users/'.$friend->id),
            ],
        ], 200);
    }

    public function accept(User $user)
    {
        $friendRequest = Friend::query()
            ->where('user_id', $user->id)
            ->where('friend_id', request()->user()->id)
            ->first();

        if ($friendRequest === null) {
            return response()->json([
                'errors' => [
                    'code' => 404,
                    'title' => 'Not Found',
                    'detail' => 'Unable to fetch friend request.',
                ],
            ], 404);
        }

        $friendRequest->update([
            'confirmed_at' => now(),
            'status' => 1,
        ]);

        return response()->json([
            'data' => [
                'type' => 'friend_requests',
                'friend_request_id' => $friendRequest->id,
                'attributes' => [
                    'confirmed_at' => $friendRequest->confirmed_at?->diffForHumans(),
                ],
            ],
            'links' => [
                'self' => url('/users/'.$friendRequest->friend_id),
            ],
        ], 200);
    }
}
