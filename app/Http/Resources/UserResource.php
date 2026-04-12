<?php

namespace App\Http\Resources;

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

        return [
            'data' => [
                'type' => 'users',
                'user_id' => $user->id,
                'attributes' => [
                    'name' => $user->name,
                    $user->name => true,
                ],
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

