<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index($activityId)
    {
        $cacheKey = "leaderboard_{$activityId}";
        // Avoid closure serialization in cache: compute, then cache array
        if (cache()->has($cacheKey)) {
            $leaderboard = cache()->get($cacheKey);
        } else {
            $leaderboard = static::computeLeaderboard($activityId);
            cache()->put($cacheKey, $leaderboard, 600);
        }
        return response()->json($leaderboard);
    }

    private static function computeLeaderboard($activityId)
    {
        $attempts = \App\Models\QuizAttempt::query()
            ->where('activity_id', $activityId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')
            ->orderBy('duration_seconds')
            ->orderBy('submitted_at')
            ->get(['user_id', 'score', 'duration_seconds', 'submitted_at']);
        $attemptsArr = [];
        foreach ($attempts as $attempt) {
            $attemptsArr[] = [
                'user_id' => $attempt->user_id,
                'score' => $attempt->score,
                'duration_seconds' => $attempt->duration_seconds,
                'submitted_at' => $attempt->submitted_at instanceof \Carbon\Carbon ? $attempt->submitted_at->toDateTimeString() : (string)$attempt->submitted_at,
            ];
        }

        $result = [];
        $rank = 1;
        $prev = null;
        $sameRankCount = 0;
        foreach ($attemptsArr as $i => $attempt) {
            $current = [
                'score' => $attempt['score'],
                'duration_seconds' => $attempt['duration_seconds'],
                'submitted_at' => $attempt['submitted_at'],
            ];
            if ($prev && $current['score'] === $prev['score'] && $current['duration_seconds'] === $prev['duration_seconds'] && $current['submitted_at'] === $prev['submitted_at']) {
                $sameRankCount++;
            } else {
                $rank += $sameRankCount;
                $sameRankCount = 1;
            }
            $result[] = [
                'user_id' => $attempt['user_id'],
                'score' => $attempt['score'],
                'duration_seconds' => $attempt['duration_seconds'],
                'submitted_at' => $attempt['submitted_at'],
                'rank' => $rank,
            ];
            $prev = $current;
        }
        return $result;
    }
}
