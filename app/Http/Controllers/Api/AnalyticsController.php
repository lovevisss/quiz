<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function activityStats(Request $request, $activityId): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->is_admin && !$user->hasRole('admin'))) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Participants: unique users who started an attempt for this activity
        $participants = QuizAttempt::where('activity_id', $activityId)
            ->distinct('user_id')
            ->count('user_id');

        // Submitted attempts: unique users who submitted at least one attempt
        $submitted = QuizAttempt::where('activity_id', $activityId)
            ->whereNotNull('submitted_at')
            ->distinct('user_id')
            ->count('user_id');

        // Completion rate: submitted / participants (0-safe)
        $completion_rate = $participants > 0 ? round($submitted / $participants, 4) : 0.0;

        // Average score: mean of all submitted attempts' scores (0-safe)
        $avg_score = QuizAttempt::where('activity_id', $activityId)
            ->whereNotNull('submitted_at')
            ->avg('score');
        $average_score = $avg_score !== null ? round($avg_score, 2) : 0.0;

        return response()->json([
            'participants' => $participants,
            'completion_rate' => $completion_rate,
            'average_score' => $average_score,
        ]);
    }
}
