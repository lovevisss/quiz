<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QuizAchievementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizAchievementController extends Controller
{
    public function __construct(private readonly QuizAchievementService $achievementService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->achievementService->buildForUser($request->user()),
        );
    }
}

