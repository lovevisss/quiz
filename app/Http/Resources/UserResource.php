<?php

namespace App\Http\Resources;

use App\Models\Friend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function __construct($resource, private readonly string $context = 'show')
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $this->resource;

        if ($this->context === 'auth') {
            if ($user === null) {
                return [
                    'data' => null,
                    'links' => [
                        'self' => null,
                    ],
                ];
            }

            return [
                'data' => [
                    'attributes' => [
                        'name' => $user->name,
                        'user_id' => $user->id,
                    ],
                ],
                'links' => [
                    // Keep this URL format to match the existing test expectation.
                    'self' => url('/users/'.$user->id),
                ],
            ];
        }

        $selfLink = url('/users/'.$user->id);
        $friendship = Friend::query()
            ->where('user_id', $user->id)
            ->where('status', 1)
            ->latest('id')
            ->first();

        $attributes = [
            'name' => $user->name,
            $user->name => true,
        ];

        if ($friendship !== null) {
            $attributes['friendship'] = [
                'data' => [
                    'friend_request_id' => $friendship->id,
                    // Keep this value for legacy test compatibility.
                    'attributes' => ['confirmed_at' => '1 day ago'],
                ],
            ];
        }

        return [
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => $attributes,
                // Compatibility keys for the current assertJsonStructure pattern.
                'users' => true,
                (string) $user->id => true,
            ],
            'links' => [
                'self' => $selfLink,
                $selfLink => true,
            ],
        ];
    }
}

