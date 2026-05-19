<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, int $activityId)
    {
        return response()->json(static::forViewer($activityId, $request->user()?->id));
    }

    public static function forViewer(int $activityId, ?int $viewerId = null): array
    {
        return collect(static::cachedLeaderboard($activityId))
            ->map(fn (array $row): array => [
                ...$row,
                'is_current_user' => $viewerId !== null && (int) $row['user_id'] === $viewerId,
            ])
            ->all();
    }

    public static function cachedLeaderboard(int $activityId): array
    {
        $cacheKey = "leaderboard_{$activityId}";

        if (cache()->has($cacheKey)) {
            return cache()->get($cacheKey, []);
        }

        $leaderboard = static::computeLeaderboard($activityId);
        cache()->put($cacheKey, $leaderboard, 600);

        return $leaderboard;
    }

    private static function computeLeaderboard(int $activityId): array
    {
        $attempts = QuizAttempt::query()
            ->where('activity_id', $activityId)
            ->whereNotNull('submitted_at')
            ->with('user:id,name')
            ->orderByDesc('score')
            ->orderBy('duration_seconds')
            ->orderBy('submitted_at')
            ->get(['user_id', 'score', 'duration_seconds', 'submitted_at']);

        $attemptsArr = [];

        foreach ($attempts as $attempt) {
            $attemptsArr[] = [
                'user_id' => $attempt->user_id,
                'user_name' => $attempt->user?->name ?? sprintf('用户 #%d', $attempt->user_id),
                'score' => $attempt->score,
                'duration_seconds' => $attempt->duration_seconds,
                'submitted_at' => $attempt->submitted_at instanceof \Carbon\Carbon
                    ? $attempt->submitted_at->toDateTimeString()
                    : (string) $attempt->submitted_at,
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
                'user_name' => $attempt['user_name'],
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
