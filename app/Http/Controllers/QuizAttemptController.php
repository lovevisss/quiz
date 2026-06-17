<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Question;
use App\Services\QuizAchievementService;
use App\Services\QuizShareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
    public function __construct(
        private readonly QuizAchievementService $achievementService,
        private readonly QuizShareService $quizShareService,
    )
    {
    }

    public function startAttempt(Request $request, $activity): JsonResponse
    {
        $attempt = QuizAttempt::create([
            'activity_id' => $activity,
            'user_id' => $request->user()->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        return response()->json($attempt, 201);
    }

    public function saveAnswer(Request $request, $attempt, $question): JsonResponse
    {
        $quizAttempt = QuizAttempt::find($attempt);
        if (! $quizAttempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        if ($quizAttempt->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $questionModel = Question::find($question);

        $payload = $request->input('answer');

        $isCorrect = $questionModel ? $this->determineCorrectness($questionModel, $payload) : false;
        $awardedScore = $isCorrect ? 1 : 0;

        $answer = QuizAnswer::updateOrCreate(
            ['attempt_id' => $attempt, 'question_id' => $question],
            [
                'answer_payload_json' => $payload,
                'is_correct' => $isCorrect,
                'awarded_score' => $awardedScore,
                'answered_at' => now(),
            ]
        );

        return response()->json($answer);
    }

    public function submitAttempt(Request $request, $attempt): JsonResponse
    {
        $quizAttempt = QuizAttempt::find($attempt);
        if (!$quizAttempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        if ($quizAttempt->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($quizAttempt->submitted_at) {
$quizAttempt->update(['anti_cheat_flags' => json_encode(['duplicate_submit' => true])]);
            $antiCheatFlags = json_decode($quizAttempt->anti_cheat_flags, true) ?? [];
            return response()->json(['error' => 'Attempt already submitted', 'anti_cheat_flags' => $antiCheatFlags], 409);
        }

        if (now()->greaterThan($quizAttempt->expires_at)) {
$quizAttempt->update(['anti_cheat_flags' => json_encode(['expired_attempt' => true])]);
            $antiCheatFlags = json_decode($quizAttempt->anti_cheat_flags, true) ?? [];
return response()->json(['error' => 'Attempt expired', 'anti_cheat_flags' => $antiCheatFlags], 422);
        }

        DB::transaction(function () use ($quizAttempt) {
            $submittedAt = now();

            $quizAttempt->update([
                'submitted_at' => $submittedAt,
                'status' => 'submitted',
                'score' => QuizAnswer::where('attempt_id', $quizAttempt->id)->sum('awarded_score'),
                'duration_seconds' => $quizAttempt->started_at
                    ? max(0, $quizAttempt->started_at->diffInSeconds($submittedAt))
                    : null,
            ]);
        });

        return response()->json($quizAttempt);
    }

    public function getResult(Request $request, $attempt): JsonResponse
    {
        $quizAttempt = QuizAttempt::find($attempt);
        if (! $quizAttempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        if ($quizAttempt->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $wrongAnswers = QuizAnswer::query()
            ->where('attempt_id', $quizAttempt->id)
            ->where('is_correct', false)
            ->with('question:id,content,type,options,answer,explanation')
            ->get();

        $totalQuestions = QuizAnswer::query()
            ->where('attempt_id', $quizAttempt->id)
            ->count();

        $correctCount = QuizAnswer::query()
            ->where('attempt_id', $quizAttempt->id)
            ->where('is_correct', true)
            ->count();

        $achievementPayload = $this->achievementService->buildForUser($request->user(), $quizAttempt);

        return response()->json([
            'activity_id' => $quizAttempt->activity_id,
            'score' => $quizAttempt->score,
            'submitted_at' => $quizAttempt->submitted_at,
            'anti_cheat_flags' => $quizAttempt->anti_cheat_flags,
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'wrong_count' => max($totalQuestions - $correctCount, 0),
            'achievements' => $achievementPayload['data'],
            'newly_unlocked_achievements' => $achievementPayload['newly_unlocked'],
            'share' => $this->quizShareService->buildPublicShareData($quizAttempt, [
                'total_questions' => $totalQuestions,
                'correct_count' => $correctCount,
                'wrong_count' => max($totalQuestions - $correctCount, 0),
            ]),
            'wrong_questions' => $wrongAnswers->map(function (QuizAnswer $answer): array {
                $question = $answer->question;
                $selectedChoice = $question ? $this->normalizeChoiceDisplay($question, $answer->answer_payload_json, false) : null;
                $correctChoice = $question ? $this->normalizeChoiceDisplay($question, $question->answer, true) : null;

                return [
                    'question_id' => $answer->question_id,
                    'content' => $question?->content,
                    'your_answer' => $answer->answer_payload_json,
                    'selected_answer_label' => $selectedChoice['label'] ?? null,
                    'selected_answer_text' => $selectedChoice['text'] ?? null,
                    'selected_answer_display' => $selectedChoice['display'] ?? '未作答',
                    'correct_answer' => $question?->answer,
                    'correct_answer_label' => $correctChoice['label'] ?? null,
                    'correct_answer_text' => $correctChoice['text'] ?? null,
                    'correct_answer_display' => $correctChoice['display'] ?? '-',
                    'explanation' => $question?->explanation,
                ];
            })->values(),
        ]);
    }

    private function determineCorrectness(Question $question, mixed $payload): bool
    {
        if ($question->type === 'text') {
            $submitted = is_array($payload) ? ($payload['value'] ?? '') : (string) $payload;
            return trim((string) $submitted) === trim((string) ($question->answer ?? ''));
        }

        $selected = is_array($payload) ? ($payload['selected_option'] ?? null) : null;
        if ($selected === null) {
            return false;
        }

        $selectedValue = trim((string) $selected);
        $correctValue = trim((string) ($question->answer ?? ''));

        if ($selectedValue === $correctValue) {
            return true;
        }

        $options = is_array($question->options) ? array_values($question->options) : [];
        $labelMap = ['A', 'B', 'C', 'D', 'E', 'F'];

        // Support answer saved as option text while client submits A/B/C labels.
        if (in_array($selectedValue, $labelMap, true) && $correctValue !== '') {
            $index = array_search($selectedValue, $labelMap, true);
            if ($index !== false && isset($options[$index]) && trim((string) $options[$index]) === $correctValue) {
                return true;
            }
        }

        // Support answer saved as A/B/C while client submits option text.
        if (! in_array($selectedValue, $labelMap, true) && in_array($correctValue, $labelMap, true)) {
            $index = array_search($correctValue, $labelMap, true);
            if ($index !== false && isset($options[$index]) && trim((string) $options[$index]) === $selectedValue) {
                return true;
            }
        }

        if ($question->type === 'multiple') {
            $submitted = collect(explode(',', $selectedValue))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->sort()
                ->values();

            $correct = collect(explode(',', $correctValue))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->sort()
                ->values();

            return $submitted->all() === $correct->all();
        }

        return false;
    }

    private function normalizeChoiceDisplay(Question $question, mixed $payload, bool $treatAsStoredAnswer): array
    {
        if ($question->type === 'text') {
            $value = $treatAsStoredAnswer
                ? trim((string) $payload)
                : trim((string) (is_array($payload) ? ($payload['value'] ?? '') : $payload));

            return [
                'label' => null,
                'text' => $value !== '' ? $value : null,
                'display' => $value !== '' ? $value : '未作答',
            ];
        }

        $rawValue = $treatAsStoredAnswer
            ? $payload
            : (is_array($payload)
                ? ($payload['selected_option'] ?? $payload['option'] ?? $payload['value'] ?? null)
                : $payload);

        $rawValue = trim((string) ($rawValue ?? ''));
        if ($rawValue === '') {
            return [
                'label' => null,
                'text' => null,
                'display' => '未作答',
            ];
        }

        $options = is_array($question->options) ? array_values($question->options) : [];
        $labelMap = ['A', 'B', 'C', 'D', 'E', 'F'];

        if ($question->type === 'multiple' || str_contains($rawValue, ',')) {
            $parts = collect(explode(',', $rawValue))
                ->map(fn ($value) => trim($value))
                ->filter()
                ->values();

            $items = $parts->map(fn (string $value) => $this->normalizeSingleChoice($value, $options, $labelMap));

            return [
                'label' => $items->pluck('label')->filter()->implode('、') ?: null,
                'text' => $items->pluck('text')->filter()->implode('；') ?: null,
                'display' => $items->pluck('display')->filter()->implode('；') ?: '未作答',
            ];
        }

        return $this->normalizeSingleChoice($rawValue, $options, $labelMap);
    }

    private function normalizeSingleChoice(string $value, array $options, array $labelMap): array
    {
        if (in_array($value, $labelMap, true)) {
            $index = array_search($value, $labelMap, true);
            $text = $index !== false && isset($options[$index]) ? trim((string) $options[$index]) : null;

            return [
                'label' => $value,
                'text' => $text,
                'display' => $text ? sprintf('%s. %s', $value, $text) : $value,
            ];
        }

        $index = collect($options)->search(fn ($option) => trim((string) $option) === $value);
        $label = $index !== false && isset($labelMap[$index]) ? $labelMap[$index] : null;

        return [
            'label' => $label,
            'text' => $value,
            'display' => $label ? sprintf('%s. %s', $label, $value) : $value,
        ];
    }
}
