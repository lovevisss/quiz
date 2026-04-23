<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizController extends Controller
{
    /**
     * Handle quiz participation.
     */
    public function participate(Request $request)
    {
        if (!config('app.features.quiz_enabled')) {
            return response()->json(['message' => 'Quiz participation is currently disabled.'], 403);
        }

        // Placeholder for actual quiz participation logic
        return response()->json(['message' => 'Participation successful.']);
    }
}