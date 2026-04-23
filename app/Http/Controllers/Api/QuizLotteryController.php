<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\QuizLotteryDraw;
use App\Models\QuizLotteryEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizLotteryController extends Controller
{
    public function show(Request $request, Activity $activity): JsonResponse
    {
        return response()->json($this->statusPayload($request, $activity));
    }

    public function store(Request $request, Activity $activity): JsonResponse
    {
        $payload = $this->statusPayload($request, $activity);

        if (! $payload['eligible']) {
            return response()->json($payload);
        }

        $entry = QuizLotteryEntry::firstOrCreate(
            [
                'activity_id' => $activity->id,
                'user_id' => $request->user()->id,
            ],
            [
                'quiz_attempt_id' => $payload['quiz_attempt_id'],
                'score' => $payload['score'],
                'eligible_at' => now(),
                'entered_at' => now(),
            ]
        );

        return response()->json([
            'eligible' => true,
            'entered' => $entry->wasRecentlyCreated,
            'entry_id' => $entry->id,
            'reason' => $entry->wasRecentlyCreated ? 'entered' : 'existing_entry',
        ]);
    }

    public function draw(Request $request, Activity $activity): JsonResponse
    {
        $validated = $request->validate([
            'count' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $count = (int) ($validated['count'] ?? 1);
        $entries = QuizLotteryEntry::query()
            ->where('activity_id', $activity->id)
            ->orderBy('user_id')
            ->orderBy('id')
            ->limit($count)
            ->get();

        $drawBatch = 'activity-'.$activity->id.'-'.now()->format('YmdHis');

        $draws = DB::transaction(function () use ($entries, $activity, $drawBatch) {
            $rows = [];

            foreach ($entries as $position => $entry) {
                $rows[] = QuizLotteryDraw::query()->create([
                    'activity_id' => $activity->id,
                    'user_id' => $entry->user_id,
                    'quiz_lottery_entry_id' => $entry->id,
                    'draw_batch' => $drawBatch,
                    'winner_position' => $position + 1,
                    'drawn_at' => now(),
                ]);
            }

            return $rows;
        });

        return response()->json([
            'draw_batch' => $drawBatch,
            'count' => count($draws),
            'winners' => collect($draws)->map(static fn (QuizLotteryDraw $draw): array => [
                'draw_id' => $draw->id,
                'user_id' => $draw->user_id,
                'entry_id' => $draw->quiz_lottery_entry_id,
                'winner_position' => $draw->winner_position,
            ])->values(),
        ]);
    }

    private function statusPayload(Request $request, Activity $activity): array
    {
        $attempt = QuizAttempt::query()
            ->where('activity_id', $activity->id)
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();

        if (! $attempt) {
            return [
                'eligible' => false,
                'entered' => false,
                'entry_id' => null,
                'quiz_attempt_id' => null,
                'score' => null,
                'reason' => 'no_submitted_attempt',
            ];
        }

        if ((int) $attempt->score < 60) {
            return [
                'eligible' => false,
                'entered' => false,
                'entry_id' => null,
                'quiz_attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'reason' => 'score_below_threshold',
            ];
        }

        return [
            'eligible' => $this->surveyCompleted($request, $activity, $attempt),
            'entered' => QuizLotteryEntry::query()
                ->where('activity_id', $activity->id)
                ->where('user_id', $request->user()->id)
                ->exists(),
            'entry_id' => QuizLotteryEntry::query()
                ->where('activity_id', $activity->id)
                ->where('user_id', $request->user()->id)
                ->value('id'),
            'quiz_attempt_id' => $attempt->id,
            'score' => $attempt->score,
            'reason' => 'eligible',
        ];
    }

    private function surveyCompleted(Request $request, Activity $activity, QuizAttempt $attempt): bool
    {
        return true;
    }
}
