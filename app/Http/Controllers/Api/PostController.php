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
}

