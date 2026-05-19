<?php

namespace App\Services;

use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class QuizAchievementService
{
    /**
     * @return array{data: array<int, array<string, mixed>>, summary: array<string, int|null>, newly_unlocked_keys: array<int, string>, newly_unlocked: array<int, array<string, mixed>>}
     */
    public function buildForUser(User $user, ?QuizAttempt $highlightAttempt = null): array
    {
        $attempts = QuizAttempt::query()
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->with(['answers' => fn ($query) => $query->orderBy('answered_at')->orderBy('id')])
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get();

        $metrics = $this->calculateMetrics($attempts);
        $achievements = $this->buildAchievements($metrics);

        $newlyUnlockedKeys = [];

        if ($highlightAttempt && $highlightAttempt->submitted_at) {
            $previousMetrics = $this->calculateMetrics(
                $attempts->reject(fn (QuizAttempt $attempt) => $attempt->id === $highlightAttempt->id)->values(),
            );
            $previousAchievements = collect($this->buildAchievements($previousMetrics))->keyBy('key');

            $newlyUnlockedKeys = collect($achievements)
                ->filter(function (array $achievement) use ($previousAchievements): bool {
                    $wasUnlocked = (bool) data_get($previousAchievements->get($achievement['key']), 'unlocked', false);

                    return $achievement['unlocked'] && ! $wasUnlocked;
                })
                ->pluck('key')
                ->values()
                ->all();
        }

        return [
            'data' => $achievements,
            'summary' => [
                'submitted_attempts' => $metrics['submitted_attempts'],
                'perfect_attempts' => $metrics['perfect_attempts'],
                'correct_answers' => $metrics['correct_answers'],
                'fastest_correct_seconds' => $metrics['fastest_correct_seconds'],
                'max_correct_streak' => $metrics['max_correct_streak'],
                'max_fast_streak' => $metrics['max_fast_streak'],
                'fastest_perfect_run_seconds' => $metrics['fastest_perfect_run_seconds'],
                'unlocked_count' => collect($achievements)->where('unlocked', true)->count(),
            ],
            'newly_unlocked_keys' => $newlyUnlockedKeys,
            'newly_unlocked' => collect($achievements)
                ->whereIn('key', $newlyUnlockedKeys)
                ->values()
                ->all(),
        ];
    }

    /**
     * @param Collection<int, QuizAttempt> $attempts
     * @return array<string, int|null>
     */
    private function calculateMetrics(Collection $attempts): array
    {
        $metrics = [
            'submitted_attempts' => $attempts->count(),
            'perfect_attempts' => 0,
            'correct_answers' => 0,
            'fastest_correct_seconds' => null,
            'max_correct_streak' => 0,
            'max_fast_streak' => 0,
            'fastest_perfect_run_seconds' => null,
        ];

        foreach ($attempts as $attempt) {
            $answers = $attempt->answers->values();

            if ($answers->isEmpty()) {
                continue;
            }

            $correctStreak = 0;
            $fastStreak = 0;
            $previousCheckpoint = $attempt->started_at;

            foreach ($answers as $answer) {
                $elapsedSinceCheckpoint = $this->secondsBetween($previousCheckpoint, $answer->answered_at);

                if ($answer->is_correct) {
                    $metrics['correct_answers']++;

                    if ($elapsedSinceCheckpoint !== null) {
                        $metrics['fastest_correct_seconds'] = $metrics['fastest_correct_seconds'] === null
                            ? $elapsedSinceCheckpoint
                            : min($metrics['fastest_correct_seconds'], $elapsedSinceCheckpoint);
                    }

                    $correctStreak++;
                    $metrics['max_correct_streak'] = max($metrics['max_correct_streak'], $correctStreak);

                    if ($elapsedSinceCheckpoint !== null && $elapsedSinceCheckpoint <= 5) {
                        $fastStreak = $fastStreak > 0 ? $fastStreak + 1 : 1;
                        $metrics['max_fast_streak'] = max($metrics['max_fast_streak'], $fastStreak);
                    } else {
                        $fastStreak = 0;
                    }
                } else {
                    $correctStreak = 0;
                    $fastStreak = 0;
                }

                $previousCheckpoint = $answer->answered_at ?? $previousCheckpoint;
            }

            $allCorrect = $answers->every(fn (QuizAnswer $answer) => (bool) $answer->is_correct);
            if (! $allCorrect) {
                continue;
            }

            $metrics['perfect_attempts']++;
            $durationSeconds = $this->attemptDurationSeconds($attempt);
            if ($durationSeconds !== null) {
                $metrics['fastest_perfect_run_seconds'] = $metrics['fastest_perfect_run_seconds'] === null
                    ? $durationSeconds
                    : min($metrics['fastest_perfect_run_seconds'], $durationSeconds);
            }
        }

        return $metrics;
    }

    /**
     * @param array<string, int|null> $metrics
     * @return array<int, array<string, mixed>>
     */
    private function buildAchievements(array $metrics): array
    {
        $submittedAttempts = (int) ($metrics['submitted_attempts'] ?? 0);
        $perfectAttempts = (int) ($metrics['perfect_attempts'] ?? 0);
        $correctAnswers = (int) ($metrics['correct_answers'] ?? 0);
        $fastestCorrectSeconds = $metrics['fastest_correct_seconds'];
        $maxCorrectStreak = (int) ($metrics['max_correct_streak'] ?? 0);
        $maxFastStreak = (int) ($metrics['max_fast_streak'] ?? 0);
        $fastestPerfectRunSeconds = $metrics['fastest_perfect_run_seconds'];

        return [
            [
                'key' => 'join_first',
                'title' => '首次参与',
                'description' => '完成 1 次答题',
                'unlocked' => $submittedAttempts >= 1,
                'progress' => min($submittedAttempts, 1).'/1',
                'progress_meta' => $this->countProgressMeta($submittedAttempts, 1, '次'),
                'icon' => 'flag',
                'tone' => 'sky',
                'badge_label' => '入门',
            ],
            [
                'key' => 'streak_3',
                'title' => '累计参与者',
                'description' => '累计完成 3 次答题',
                'unlocked' => $submittedAttempts >= 3,
                'progress' => min($submittedAttempts, 3).'/3',
                'progress_meta' => $this->countProgressMeta($submittedAttempts, 3, '次'),
                'icon' => 'calendar-check-2',
                'tone' => 'indigo',
                'badge_label' => '坚持',
            ],
            [
                'key' => 'perfect_score',
                'title' => '满分成就',
                'description' => '任意一次测验获得满分',
                'unlocked' => $perfectAttempts >= 1,
                'progress' => min($perfectAttempts, 1).'/1',
                'progress_meta' => $this->countProgressMeta($perfectAttempts, 1, '次'),
                'icon' => 'trophy',
                'tone' => 'amber',
                'badge_label' => '荣耀',
            ],
            [
                'key' => 'zero_breakthrough',
                'title' => '零的突破',
                'description' => '首次答题正确 1 题',
                'unlocked' => $correctAnswers >= 1,
                'progress' => min($correctAnswers, 1).'/1',
                'progress_meta' => $this->countProgressMeta($correctAnswers, 1, '题'),
                'icon' => 'sparkles',
                'tone' => 'emerald',
                'badge_label' => '突破',
            ],
            [
                'key' => 'lightning_hand',
                'title' => '闪电手',
                'description' => '10 秒内答对 1 题',
                'unlocked' => $fastestCorrectSeconds !== null && $fastestCorrectSeconds <= 10,
                'progress' => $fastestCorrectSeconds === null ? '暂无记录' : '最快 '.$fastestCorrectSeconds.' 秒',
                'progress_meta' => $this->timeThresholdProgressMeta($fastestCorrectSeconds, 10),
                'icon' => 'zap',
                'tone' => 'yellow',
                'badge_label' => '速度',
            ],
            [
                'key' => 'rapid_master',
                'title' => '速算大师',
                'description' => '连续 10 题在 5 秒内答对',
                'unlocked' => $maxFastStreak >= 10,
                'progress' => min($maxFastStreak, 10).'/10',
                'progress_meta' => $this->countProgressMeta($maxFastStreak, 10, '题'),
                'icon' => 'brain-circuit',
                'tone' => 'violet',
                'badge_label' => '极限',
            ],
            [
                'key' => 'speed_run',
                'title' => '极速通关',
                'description' => '30 秒内全部答对',
                'unlocked' => $fastestPerfectRunSeconds !== null && $fastestPerfectRunSeconds <= 30,
                'progress' => $fastestPerfectRunSeconds === null ? '暂无记录' : '最快 '.$fastestPerfectRunSeconds.' 秒',
                'progress_meta' => $this->timeThresholdProgressMeta($fastestPerfectRunSeconds, 30),
                'icon' => 'rocket',
                'tone' => 'rose',
                'badge_label' => '通关',
            ],
            [
                'key' => 'triple_star',
                'title' => '三连星',
                'description' => '连续答对 3 题',
                'unlocked' => $maxCorrectStreak >= 3,
                'progress' => min($maxCorrectStreak, 3).'/3',
                'progress_meta' => $this->countProgressMeta($maxCorrectStreak, 3, '题'),
                'icon' => 'star',
                'tone' => 'orange',
                'badge_label' => '连击',
            ],
            [
                'key' => 'perfect_ten',
                'title' => '十全十美',
                'description' => '连续答对 10 题',
                'unlocked' => $maxCorrectStreak >= 10,
                'progress' => min($maxCorrectStreak, 10).'/10',
                'progress_meta' => $this->countProgressMeta($maxCorrectStreak, 10, '题'),
                'icon' => 'crown',
                'tone' => 'fuchsia',
                'badge_label' => '巅峰',
            ],
        ];
    }

    /**
     * @return array{current:int, target:int, percent:int, unit:string, direction:string}
     */
    private function countProgressMeta(int $current, int $target, string $unit): array
    {
        $safeTarget = max($target, 1);

        return [
            'current' => max(0, $current),
            'target' => $safeTarget,
            'percent' => $this->clampPercent((int) round((max(0, $current) / $safeTarget) * 100)),
            'unit' => $unit,
            'direction' => 'up',
        ];
    }

    /**
     * @return array{current:int|null, target:int, percent:int, unit:string, direction:string}
     */
    private function timeThresholdProgressMeta(?int $current, int $target): array
    {
        $safeTarget = max($target, 1);

        if ($current === null || $current <= 0) {
            return [
                'current' => null,
                'target' => $safeTarget,
                'percent' => 0,
                'unit' => '秒',
                'direction' => 'down',
            ];
        }

        return [
            'current' => $current,
            'target' => $safeTarget,
            'percent' => $this->clampPercent((int) round(($safeTarget / $current) * 100)),
            'unit' => '秒',
            'direction' => 'down',
        ];
    }

    private function clampPercent(int $percent): int
    {
        return max(0, min(100, $percent));
    }

    private function attemptDurationSeconds(QuizAttempt $attempt): ?int
    {
        if ($attempt->duration_seconds !== null) {
            return (int) $attempt->duration_seconds;
        }

        return $this->secondsBetween($attempt->started_at, $attempt->submitted_at);
    }

    private function secondsBetween(mixed $start, mixed $end): ?int
    {
        if (! $start || ! $end) {
            return null;
        }

        return max(0, $start->diffInSeconds($end));
    }
}

