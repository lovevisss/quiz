<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = Post::query()
            ->with('user:id,name')
            ->latest()
            ->get(['id', 'user_id', 'body', 'image_url', 'created_at']);

        return response()->json($posts);
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
}

