<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    public function index()
    {
        $this->syncManagedTagsFromQuestions();

        $questions = Question::query()
            ->withCount([
                'feedback as likes_count' => fn ($query) => $query->where('liked', true),
                'feedback as dislikes_count' => fn ($query) => $query->where('liked', false),
            ])
            ->with([
                'feedback' => fn ($query) => $query
                    ->whereNotNull('correction_text')
                    ->latest()
                    ->with('user:id,name'),
            ])
            ->orderByDesc('id')
            ->get();

        return Inertia::render('Admin/Questions', [
            'questions' => $questions,
            'availableTags' => QuestionTag::query()
                ->orderBy('name')
                ->get(['id', 'name']),
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

        $validated['tags'] = QuestionTag::normalizeNames($validated['tags'] ?? []);
        QuestionTag::syncNames($validated['tags']);

        $question = Question::create($validated);
        Log::info('Admin created question', [
            'admin_id' => Auth::id(),
            'question_id' => $question->id,
            'data' => $validated,
        ]);
        return redirect()->route('admin.questions.index')->with('success', 'Question created');
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
        return redirect()->route('admin.questions.index')->with('success', 'Question updated');
    }

    public function destroy(Question $question)
    {
        $question->delete();
        Log::info('Admin deleted question', [
            'admin_id' => Auth::id(),
            'question_id' => $question->id,
        ]);
        return redirect()->route('admin.questions.index')->with('success', 'Question deleted');
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
}
