<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\QuizAttempt;
use App\Models\QuizCertificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizCertificateController extends Controller
{
    public function show(Request $request, Activity $activity): JsonResponse
    {
        $certificate = QuizCertificate::where('activity_id', $activity->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($certificate) {
            return response()->json([
                'eligible' => true,
                'issued' => false,
                'certificate_id' => $certificate->id,
                'reason' => 'existing_certificate',
            ]);
        }

        $attempt = QuizAttempt::where('activity_id', $activity->id)
            ->where('user_id', $request->user()->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->first();

        if (! $attempt) {
            return response()->json([
                'eligible' => false,
                'issued' => false,
                'certificate_id' => null,
                'reason' => 'no_submitted_attempt',
            ]);
        }

        if ((int) $attempt->score < 60) {
            return response()->json([
                'eligible' => false,
                'issued' => false,
                'certificate_id' => null,
                'reason' => 'score_below_threshold',
            ]);
        }

        $certificate = QuizCertificate::firstOrCreate(
            [
                'activity_id' => $activity->id,
                'user_id' => $request->user()->id,
            ],
            [
                'quiz_attempt_id' => $attempt->id,
                'score' => $attempt->score,
                'issued_at' => now(),
            ]
        );

        return response()->json([
            'eligible' => true,
            'issued' => true,
            'certificate_id' => $certificate->id,
            'reason' => 'issued',
        ]);
    }
}
