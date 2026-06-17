<?php

namespace App\Http\Controllers;

use App\Models\QuizAttempt;
use App\Services\QuizShareService;
use Illuminate\Contracts\View\View;

class QuizShareController extends Controller
{
    public function __construct(private readonly QuizShareService $quizShareService)
    {
    }

    public function show(QuizAttempt $attempt): View
    {
        $share = $this->quizShareService->buildPublicShareData($attempt);

        return view('quiz.share', [
            'share' => $share,
        ]);
    }
}

