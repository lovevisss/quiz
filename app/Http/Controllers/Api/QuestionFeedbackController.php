<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuestionFeedbackController extends Controller
{
    public function like(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate([
            'liked' => 'required|boolean',
        ]);

        $feedback = QuestionFeedback::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'user_id' => $request->user()->id,
            ],
            [
                'liked' => (bool) $data['liked'],
            ],
        );

        return response()->json([
            'liked' => $feedback->liked,
        ]);
    }

    public function correction(Request $request, Question $question): JsonResponse
    {
        $data = $request->validate([
            'correction_text' => 'required|string|min:5|max:2000',
        ]);

        QuestionFeedback::query()->updateOrCreate(
            [
                'question_id' => $question->id,
                'user_id' => $request->user()->id,
            ],
            [
                'correction_text' => $data['correction_text'],
                'correction_status' => 'pending',
            ],
        );

        return response()->json([
            'message' => 'Correction submitted.',
        ], 201);
    }
}

