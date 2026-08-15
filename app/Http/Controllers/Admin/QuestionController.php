<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class QuestionController extends Controller
{
    public function index(Request $request): Response
    {
        $this->syncManagedTagsFromQuestions();

        $filters = $request->validate([
            'q' => 'nullable|string|max:120',
            'tag' => 'nullable|string|max:50',
            'type' => 'nullable|in:single,multiple,text',
            'status' => 'nullable|in:active,inactive',
            'sort' => 'nullable|in:latest,likes_desc,feedback_desc,oldest',
        ]);

        $keyword = trim((string) ($filters['q'] ?? ''));
        $selectedTag = trim((string) ($filters['tag'] ?? ''));
        $selectedType = (string) ($filters['type'] ?? '');
        $selectedStatus = (string) ($filters['status'] ?? '');
        $sort = (string) ($filters['sort'] ?? 'latest');

        $questionsQuery = Question::query()
            ->withCount([
                'feedback as likes_count' => fn ($query) => $query->where('liked', true),
                'feedback as dislikes_count' => fn ($query) => $query->where('liked', false),
                'feedback as feedback_count' => fn ($query) => $query->whereNotNull('correction_text'),
            ])
            ->with([
                'feedback' => fn ($query) => $query
                    ->whereNotNull('correction_text')
                    ->latest()
                    ->with('user:id,name'),
            ]);

        if ($keyword !== '') {
            $questionsQuery->where(function ($query) use ($keyword): void {
                $query->where('content', 'like', "%{$keyword}%")
                    ->orWhere('answer', 'like', "%{$keyword}%")
                    ->orWhere('explanation', 'like', "%{$keyword}%");
            });
        }

        if ($selectedTag !== '') {
            $questionsQuery->whereJsonContains('tags', $selectedTag);
        }

        if ($selectedType !== '') {
            $questionsQuery->where('type', $selectedType);
        }

        if ($selectedStatus !== '') {
            $questionsQuery->where('status', $selectedStatus === 'active');
        }

        match ($sort) {
            'likes_desc' => $questionsQuery->orderByDesc('likes_count')->orderByDesc('id'),
            'feedback_desc' => $questionsQuery->orderByDesc('feedback_count')->orderByDesc('id'),
            'oldest' => $questionsQuery->orderBy('id'),
            default => $questionsQuery->orderByDesc('id'),
        };

        $questions = $questionsQuery
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Questions', [
            'questions' => $questions,
            'availableTags' => $this->availableTags(),
            'filters' => [
                'q' => $keyword,
                'tag' => $selectedTag,
                'type' => $selectedType,
                'status' => $selectedStatus,
                'sort' => $sort,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->syncManagedTagsFromQuestions();

        return Inertia::render('Admin/QuestionCreate', [
            'availableTags' => $this->availableTags(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|string',
            'options' => 'nullable|array',
            'answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'option_explanations' => 'nullable|array',
            'difficulty' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|boolean',
        ]);

        $validated['status'] = (bool) ($validated['status'] ?? true);
        $validated['tags'] = QuestionTag::normalizeNames($validated['tags'] ?? []);
        QuestionTag::syncNames($validated['tags']);

        $question = Question::create($validated);
        Log::info('Admin created question', [
            'admin_id' => Auth::id(),
            'question_id' => $question->id,
            'data' => $validated,
        ]);

        return $this->redirectBackOrTo($request, route('admin.questions.create'))
            ->with('success', 'Question created');
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'content' => 'sometimes|required|string',
            'type' => 'sometimes|required|string',
            'options' => 'nullable|array',
            'answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'option_explanations' => 'nullable|array',
            'difficulty' => 'nullable|integer|min:1|max:5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'status' => 'nullable|boolean',
        ]);

        if (array_key_exists('status', $validated)) {
            $validated['status'] = (bool) $validated['status'];
        }

        if (array_key_exists('tags', $validated)) {
            $validated['tags'] = QuestionTag::normalizeNames($validated['tags'] ?? []);
            QuestionTag::syncNames($validated['tags']);
        }

        $question->update($validated);
        Log::info('Admin updated question', [
            'admin_id' => Auth::id(),
            'question_id' => $question->id,
            'data' => $validated,
        ]);

        return $this->redirectBackOrTo($request, route('admin.questions.index'))
            ->with('success', 'Question updated');
    }

    public function destroy(Request $request, Question $question)
    {
        $question->delete();
        Log::info('Admin deleted question', [
            'admin_id' => Auth::id(),
            'question_id' => $question->id,
        ]);

        return $this->redirectBackOrTo($request, route('admin.questions.index'))
            ->with('success', 'Question deleted');
    }

    private function availableTags()
    {
        return QuestionTag::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function syncManagedTagsFromQuestions(): void
    {
        QuestionTag::syncNames(
            Question::query()
                ->get(['tags'])
                ->flatMap(fn (Question $question): array => is_array($question->tags) ? $question->tags : [])
                ->all(),
        );
    }

    private function redirectBackOrTo(Request $request, string $fallback): RedirectResponse
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            return redirect()->to($referer);
        }

        return redirect()->to($fallback);
    }
}
