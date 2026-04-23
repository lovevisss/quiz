<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Models\QuizAnswer;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QuizAttemptController extends Controller
{
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
            $quizAttempt->update([
                'submitted_at' => now(),
                'status' => 'submitted',
                'score' => QuizAnswer::where('attempt_id', $quizAttempt->id)->sum('awarded_score'),
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
            ->with('question:id,content,answer,explanation,option_explanations')
            ->get();

        return response()->json([
            'score' => $quizAttempt->score,
            'submitted_at' => $quizAttempt->submitted_at,
            'anti_cheat_flags' => $quizAttempt->anti_cheat_flags,
            'wrong_questions' => $wrongAnswers->map(function (QuizAnswer $answer): array {
                return [
                    'question_id' => $answer->question_id,
                    'content' => $answer->question?->content,
                    'your_answer' => $answer->answer_payload_json,
                    'correct_answer' => $answer->question?->answer,
                    'explanation' => $answer->question?->explanation,
                    'option_explanations' => $answer->question?->option_explanations ?? [],
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
}
