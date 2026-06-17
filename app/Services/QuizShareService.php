<?php

namespace App\Services;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\URL;

class QuizShareService
{
    /**
     * @param  array{total_questions?: int, correct_count?: int, wrong_count?: int}|null  $metrics
     * @return array{
     *     title: string,
     *     description: string,
     *     link: string,
     *     image: string,
     *     activity_name: string,
     *     total_questions: int,
     *     correct_count: int,
     *     wrong_count: int,
     *     score: int,
     *     submitted_at: string|null
     * }
     */
    public function buildPublicShareData(QuizAttempt $attempt, ?array $metrics = null): array
    {
        $attempt->loadMissing('activity:id,name');

        $resolvedMetrics = $this->resolveMetrics($attempt, $metrics);
        $activityName = trim((string) ($attempt->activity?->name ?? '在线答题'));
        $totalQuestions = $resolvedMetrics['total_questions'];
        $correctCount = $resolvedMetrics['correct_count'];
        $wrongCount = $resolvedMetrics['wrong_count'];
        $score = (int) ($attempt->score ?? 0);

        $title = $totalQuestions > 0
            ? sprintf('%s：我答对了 %d/%d 题，来挑战一下？', $activityName, $correctCount, $totalQuestions)
            : sprintf('%s：我刚完成一次答题挑战，来试试看？', $activityName);

        $description = sprintf(
            '本次得分 %d 分，答对 %d 题，错题 %d 题。点击查看成绩卡。',
            $score,
            $correctCount,
            $wrongCount,
        );

        return [
            'title' => $title,
            'description' => $description,
            'link' => URL::signedRoute('quiz.share.attempt', ['attempt' => $attempt]),
            'image' => url('/apple-touch-icon.png'),
            'activity_name' => $activityName,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'score' => $score,
            'submitted_at' => optional($attempt->submitted_at)?->toIso8601String(),
        ];
    }

    /**
     * @param  array{total_questions?: int, correct_count?: int, wrong_count?: int}|null  $metrics
     * @return array{total_questions: int, correct_count: int, wrong_count: int}
     */
    private function resolveMetrics(QuizAttempt $attempt, ?array $metrics = null): array
    {
        if (is_array($metrics)) {
            $totalQuestions = max(0, (int) ($metrics['total_questions'] ?? 0));
            $correctCount = max(0, min($totalQuestions, (int) ($metrics['correct_count'] ?? 0)));
            $wrongCount = array_key_exists('wrong_count', $metrics)
                ? max(0, (int) $metrics['wrong_count'])
                : max($totalQuestions - $correctCount, 0);

            return [
                'total_questions' => $totalQuestions,
                'correct_count' => $correctCount,
                'wrong_count' => $wrongCount,
            ];
        }

        $totalQuestions = QuizAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->count();

        $correctCount = QuizAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->where('is_correct', true)
            ->count();

        return [
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'wrong_count' => max($totalQuestions - $correctCount, 0),
        ];
    }
}

