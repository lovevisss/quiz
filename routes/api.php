<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\QuizAchievementController;
use App\Http\Controllers\Api\QuestionFeedbackController;
use App\Http\Controllers\Api\QuizLotteryController;
use App\Http\Controllers\Api\QuizCertificateController;
use App\Http\Controllers\Api\WeChatShareController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\QuizAttemptController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:api,web'])->group(function () {
    Route::get('/surveys/activities/{activity}/current', [\App\Http\Controllers\Api\SurveyController::class, 'current']);
    Route::post('/surveys/activities/{activity}/responses', [\App\Http\Controllers\Api\SurveyController::class, 'submit']);
    Route::get('/quiz/activities/{activity}/leaderboard', [\App\Http\Controllers\Api\LeaderboardController::class, 'index'])
        ->name('api.quiz.activities.leaderboard');
    Route::get('/quiz/activities/{activity}/certificate', [QuizCertificateController::class, 'show'])
        ->name('api.quiz.activities.certificate');
    Route::get('/quiz/activities/{activity}/lottery', [QuizLotteryController::class, 'show'])
        ->name('api.quiz.activities.lottery.show');
    Route::post('/quiz/activities/{activity}/lottery', [QuizLotteryController::class, 'store'])
        ->name('api.quiz.activities.lottery.store');
    Route::post('/quiz/activities/{activity}/attempts', [QuizAttemptController::class, 'startAttempt']);
    Route::put('/quiz/attempts/{attempt}/answers/{question}', [QuizAttemptController::class, 'saveAnswer']);
    Route::post('/quiz/attempts/{attempt}/submit', [QuizAttemptController::class, 'submitAttempt']);
    Route::get('/quiz/attempts/{attempt}/result', [QuizAttemptController::class, 'getResult']);
    Route::post('/quiz/questions/{question}/like', [QuestionFeedbackController::class, 'like'])->name('api.quiz.questions.like');
    Route::post('/quiz/questions/{question}/corrections', [QuestionFeedbackController::class, 'correction'])->name('api.quiz.questions.correction');
    Route::get('/quiz/achievements', [QuizAchievementController::class, 'index'])
        ->name('api.quiz.achievements');
    Route::post('/quiz/wechat/share-config', WeChatShareController::class)
        ->name('api.quiz.wechat.share-config');

    Route::post('/quiz/activities/{activity}/lottery/draw', [QuizLotteryController::class, 'draw'])
        ->middleware('role:admin')
        ->name('api.quiz.activities.lottery.draw');

    // Admin analytics endpoint
    Route::get('/quiz/activities/{activity}/analytics', [\App\Http\Controllers\Api\AnalyticsController::class, 'activityStats'])
        ->name('api.quiz.activities.analytics');
});

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::middleware('auth')->post('/posts/{post}/like', [PostController::class, 'like'])->name('api.posts.like');
Route::middleware('web')->get('/auth/user', [UserController::class, 'authUser'])->name('api.auth.user');

Route::get('quiz/activities/current', function () {
    $activity = \App\Models\Activity::query()
        ->with('paperStrategy:id,name,mode,config,status')
        ->where('enabled', true)
        ->where(function ($query): void {
            $query->whereNull('start_date')
                ->orWhere('start_date', '<=', now());
        })
        ->where(function ($query): void {
            $query->whereNull('end_date')
                ->orWhere('end_date', '>=', now());
        })
        ->latest('id')
        ->first(['id', 'name', 'description', 'start_date', 'end_date', 'enabled', 'paper_strategy_id']);

    if (! $activity) {
        return response()->json(['data' => null]);
    }

    return response()->json([
        'data' => [
            'id' => $activity->id,
            'name' => $activity->name,
            'description' => $activity->description,
            'start_date' => $activity->start_date,
            'end_date' => $activity->end_date,
            'enabled' => $activity->enabled,
            'paper_strategy_id' => $activity->paper_strategy_id,
            'paper_strategy' => $activity->paperStrategy ? [
                'id' => $activity->paperStrategy->id,
                'name' => $activity->paperStrategy->name,
                'mode' => $activity->paperStrategy->mode,
            ] : null,
        ],
    ]);
})->name('api.quiz.activities.current');

Route::get('quiz/activities/{activity}/questions', function (\App\Models\Activity $activity) {
    $baseQuestionQuery = fn () => \App\Models\Question::query()
        ->withCount([
            'feedback as likes_count' => fn ($query) => $query->where('liked', true),
            'feedback as dislikes_count' => fn ($query) => $query->where('liked', false),
        ])
        ->where('status', true)
        ->select(['id', 'content', 'type', 'options', 'explanation', 'option_explanations', 'tags']);

    $fallbackQuestions = fn () => $baseQuestionQuery()
        ->inRandomOrder()
        ->limit(10)
        ->get();

    if (! $activity->enabled) {
        return response()->json([
            'data' => [],
            'message' => 'Activity is disabled.',
        ]);
    }

    $strategy = $activity->paperStrategy;

    if (! $strategy || ! $strategy->status) {
        $questions = $fallbackQuestions();

        return response()->json([
            'data' => $questions,
            'strategy' => null,
        ]);
    }

    $config = is_array($strategy->config) ? $strategy->config : [];

    if ($strategy->mode === 'fixed') {
        $questionIds = collect($config['question_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $orderMap = $questionIds
            ->values()
            ->flip();

        $questions = $questionIds->isNotEmpty()
            ? $baseQuestionQuery()
                ->whereIn('id', $questionIds)
                ->get()
                ->sortBy(function ($question) use ($orderMap): int {
                    return (int) ($orderMap[(int) $question->id] ?? PHP_INT_MAX);
                })
                ->values()
            : collect();

        if ($questions->isEmpty()) {
            $questions = $fallbackQuestions();
        }

        return response()->json([
            'data' => $questions,
            'strategy' => [
                'id' => $strategy->id,
                'name' => $strategy->name,
                'mode' => $strategy->mode,
            ],
        ]);
    }

    if ($strategy->mode === 'random' && isset($config['tag_ratios']) && is_array($config['tag_ratios'])) {
        $count = max(1, min(50, (int) ($config['count'] ?? 10)));
        $selected = collect();

        foreach ($config['tag_ratios'] as $tag => $ratio) {
            $normalizedTag = trim((string) $tag);
            if ($normalizedTag === '') {
                continue;
            }

            $quota = (int) ceil($count * max(0.0, (float) $ratio));
            if ($quota <= 0) {
                continue;
            }

            $chunk = \App\Models\Question::query()
                ->withCount([
                    'feedback as likes_count' => fn ($query) => $query->where('liked', true),
                    'feedback as dislikes_count' => fn ($query) => $query->where('liked', false),
                ])
                ->where('status', true)
                ->whereJsonContains('tags', $normalizedTag)
                ->inRandomOrder()
                ->limit($quota)
                ->get(['id', 'content', 'type', 'options', 'explanation', 'option_explanations', 'tags']);

            $selected = $selected->concat($chunk->all());
        }

        $selected = $selected
            ->unique('id')
            ->values();

        if ($selected->count() < $count) {
            $fill = \App\Models\Question::query()
                ->withCount([
                    'feedback as likes_count' => fn ($query) => $query->where('liked', true),
                    'feedback as dislikes_count' => fn ($query) => $query->where('liked', false),
                ])
                ->where('status', true)
                ->whereNotIn('id', $selected->pluck('id'))
                ->inRandomOrder()
                ->limit($count - $selected->count())
                ->get(['id', 'content', 'type', 'options', 'explanation', 'option_explanations', 'tags']);

            $selected = $selected->concat($fill->all())->values();
        }

        return response()->json([
            'data' => $selected,
            'strategy' => [
                'id' => $strategy->id,
                'name' => $strategy->name,
                'mode' => $strategy->mode,
            ],
        ]);
    }

    $count = max(1, min(50, (int) ($config['count'] ?? 10)));

    $questions = $baseQuestionQuery()
        ->inRandomOrder()
        ->limit($count)
        ->get();

    return response()->json([
        'data' => $questions,
        'strategy' => [
            'id' => $strategy->id,
            'name' => $strategy->name,
            'mode' => $strategy->mode,
        ],
    ]);
})->name('api.quiz.activities.questions');

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('/roles', [RoleController::class, 'index'])->name('api.roles.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('api.users.show');
    Route::get('/users/{user}/posts', [PostController::class, 'byUser'])->name('api.users.posts.index');
    Route::post('/users/{user}/roles', [RoleController::class, 'assignToUser'])
        ->middleware('role:admin')
        ->name('api.users.roles.assign');
    Route::delete('/users/{user}/roles/{role}', [RoleController::class, 'removeFromUser'])
        ->middleware('role:admin')
        ->name('api.users.roles.remove');
});
