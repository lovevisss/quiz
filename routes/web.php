<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\CasController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/auth/cas/redirect', [CasController::class, 'redirect'])
    ->middleware('guest')
    ->name('cas.redirect');
Route::get('/auth/cas/callback', [CasController::class, 'callback'])
    ->middleware('guest')
    ->name('cas.callback');
Route::get('/auth/cas/logout', [CasController::class, 'logout'])
    ->middleware('auth')
    ->name('cas.logout');

Route::inertia('/index', 'Index')->name('index');
Route::inertia('/friends', 'Index')->name('friends');
Route::inertia('/watch', 'Index')->name('watch');
Route::inertia('/user/{id}', 'Users/Show')->name('users.show');
Route::get('/apis/posts', [PostController::class, 'legacyIndex'])->name('apis.posts.index');
Route::post('api/friend-requests', [\App\Http\Controllers\Api\FriendRequestController::class, 'store'])->name('api.friend-requests.store');
Route::post('api/friend-requests/{user}/accept', [\App\Http\Controllers\Api\FriendRequestController::class, 'accept'])->name('api.friend-requests.accept');
Route::post('api/friend-requests/{user}/reject', [\App\Http\Controllers\Api\FriendRequestController::class, 'reject'])->name('api.friend-requests.reject');
Route::prefix('quiz')->group(function () {
    Route::post('/participate', [\App\Http\Controllers\QuizController::class, 'participate'])->name('quiz.participate');
    Route::inertia('/', 'Quiz/ActivityHome')->name('quiz.index');
    Route::inertia('/question', 'Quiz/Question')->name('quiz.question');
    Route::inertia('/result', 'Quiz/Result')->name('quiz.result');
    Route::inertia('/leaderboard', 'Quiz/Leaderboard')->name('quiz.leaderboard');
    Route::inertia('/certificate', 'Quiz/Certificate')->name('quiz.certificate');
});

Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('activities', [\App\Http\Controllers\Admin\ActivityController::class, 'index'])->name('admin.activities.index');
    Route::post('activities', [\App\Http\Controllers\Admin\ActivityController::class, 'store'])->name('admin.activities.store');
    Route::put('activities/{activity}', [\App\Http\Controllers\Admin\ActivityController::class, 'update'])->name('admin.activities.update');
    Route::patch('activities/{activity}/toggle-status', [\App\Http\Controllers\Admin\ActivityController::class, 'toggleStatus'])->name('admin.activities.toggle-status');

    Route::get('questions', [\App\Http\Controllers\Admin\QuestionController::class, 'index'])->name('admin.questions.index');
    Route::post('questions', [\App\Http\Controllers\Admin\QuestionController::class, 'store'])->name('admin.questions.store');
    Route::put('questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'update'])->name('admin.questions.update');
    Route::delete('questions/{question}', [\App\Http\Controllers\Admin\QuestionController::class, 'destroy'])->name('admin.questions.destroy');
    Route::post('questions/import', [\App\Http\Controllers\Admin\QuestionImportController::class, 'import'])->name('admin.questions.import');
    Route::get('questions/import/template', [\App\Http\Controllers\Admin\QuestionImportController::class, 'template'])->name('admin.questions.import.template');

    // PaperStrategy CRUD
    Route::get('paper_strategies', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'index'])->name('admin.paper_strategies.index');
    Route::get('paper_strategies/create', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'create'])->name('admin.paper_strategies.create');
    Route::post('paper_strategies', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'store'])->name('admin.paper_strategies.store');
    Route::get('paper_strategies/{paper_strategy}/edit', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'edit'])->name('admin.paper_strategies.edit');
    Route::put('paper_strategies/{paper_strategy}', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'update'])->name('admin.paper_strategies.update');
    Route::delete('paper_strategies/{paper_strategy}', [\App\Http\Controllers\Admin\PaperStrategyController::class, 'destroy'])->name('admin.paper_strategies.destroy');

    // SurveyTemplate CRUD
    Route::get('survey_templates', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'index'])->name('admin.survey_templates.index');
    Route::get('survey_templates/create', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'create'])->name('admin.survey_templates.create');
    Route::post('survey_templates', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'store'])->name('admin.survey_templates.store');
    Route::get('survey_templates/{survey_template}/edit', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'edit'])->name('admin.survey_templates.edit');
    Route::put('survey_templates/{survey_template}', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'update'])->name('admin.survey_templates.update');
    Route::delete('survey_templates/{survey_template}', [\App\Http\Controllers\Admin\SurveyTemplateController::class, 'destroy'])->name('admin.survey_templates.destroy');

    // SurveyQuestion CRUD (nested)
    Route::get('survey_templates/{survey_template}/questions', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'index'])->name('admin.survey_questions.index');
    Route::get('survey_templates/{survey_template}/questions/create', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'create'])->name('admin.survey_questions.create');
    Route::post('survey_templates/{survey_template}/questions', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'store'])->name('admin.survey_questions.store');
    Route::get('survey_templates/{survey_template}/questions/{survey_question}/edit', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'edit'])->name('admin.survey_questions.edit');
    Route::put('survey_templates/{survey_template}/questions/{survey_question}', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'update'])->name('admin.survey_questions.update');
    Route::delete('survey_templates/{survey_template}/questions/{survey_question}', [\App\Http\Controllers\Admin\SurveyQuestionController::class, 'destroy'])->name('admin.survey_questions.destroy');

    // Analytics, Export, AuditLog
    Route::get('analytics/overview', [\App\Http\Controllers\Admin\AnalyticsController::class, 'overview'])->name('admin.analytics.overview');
    Route::post('export/tasks', [\App\Http\Controllers\Admin\ExportController::class, 'create'])->name('admin.export.create');
    Route::get('export/tasks/{id}', [\App\Http\Controllers\Admin\ExportController::class, 'status'])->name('admin.export.status');
    Route::get('export/tasks/{id}/download', [\App\Http\Controllers\Admin\ExportController::class, 'download'])->name('admin.export.download');
    Route::get('audit/logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit.logs');
});

Route::post('/auth/sso/mock/callback', [\App\Http\Controllers\Auth\SchoolSsoController::class, 'mockCallback'])->name('auth.sso.mock.callback');


require __DIR__.'/settings.php';
