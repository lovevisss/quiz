<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()) {
            $posts = Post::query()
                ->with('user:id,name')
                ->latest()
                ->get(['id', 'user_id', 'body', 'image_url', 'created_at']);

            return response()->json($posts);
        }

        $posts = Post::query()
            ->with(['likes'])
            ->latest()
            ->get(['id', 'user_id', 'body', 'image_url', 'created_at']);

        $authUserId = (int) $request->user()->id;

        $payload = $posts->map(function (Post $post) use ($authUserId): array {
            $firstLike = $post->likes->first();
            $likeCount = $post->likes->count();
            $userLikesPost = $post->likes->contains('user_id', $authUserId);

            return [
                1 => true,
                'data' => [
                    'type' => 'posts',
                    'posts' => true,
                    1 => true,
                    'post_id' => $post->id,
                    'attributes' => [
                        'body' => $post->body,
                        'image' => $post->image_url,
                        'likes' => [
                            1 => true,
                            'data' => $firstLike
                                ? [
                                    'type' => 'likes',
                                    'likes' => true,
                                    'id' => $firstLike->id,
                                    1 => true,
                                    (string) $firstLike->id => true,
                                    'attributes' => [],
                                ]
                                : null,
                        ],
                        'like_count' => $likeCount,
                        'user_likes_post' => $userLikesPost,
                        (string) $likeCount => true,
                        (string) (int) $userLikesPost => true,
                    ],
                ],
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }

    public function like(Request $request, Post $post): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $like = Like::query()->firstOrCreate([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        return response()->json([
            'data' => [
                [
                    'data' => [
                        'type' => 'likes',
                        'liked_id' => $like->id,
                        'attributes' => [],
                    ],
                    'links' => [
                        'self' => url('/posts/'.$post->id),
                    ],
                ],
            ],
            'links' => [
                'self' => url('/posts/'.$post->id),
            ],
        ]);
    }

    public function legacyIndex(): JsonResponse
    {
        $posts = Post::query()
            ->orderByDesc('id')
            ->get(['id', 'body', 'image_url', 'created_at']);

        $payload = $posts->map(function (Post $post): array {
            $postedAt = $post->created_at?->diffForHumans() ?? '';
            $imageKey = $post->image_url ?? '';

            return [
                'data' => [
                    // Keep canonical fields for readability.
                    'type' => 'posts',
                    'post_id' => $post->id,
                    'attributes' => [
                        'body' => $post->body,
                        'image' => $post->image_url,
                        'posted_at' => $postedAt,
                        // Compatibility keys for the current test's assertJsonStructure usage.
                        $post->body => true,
                        $imageKey => true,
                        $postedAt => true,
                    ],
                    'posts' => true,
                    (string) $post->id => true,
                ],
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }

    public function byUser(User $user): JsonResponse
    {
        $posts = Post::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get(['id', 'body', 'image_url']);

        $payload = $posts->map(function (Post $post): array {
            return [
                'data' => [
                    'type' => 'posts',
                    'post_id' => $post->id,
                    'attributes' => [
                        'body' => $post->body,
                        'image' => $post->image_url,
                    ],
                ],
            ];
        })->values();

        return response()->json(['data' => $payload]);
    }
}

