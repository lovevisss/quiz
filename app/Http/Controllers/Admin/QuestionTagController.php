<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaperStrategy;
use App\Models\Question;
use App\Models\QuestionTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class QuestionTagController extends Controller
{
    public function index(): Response
    {
        $this->syncManagedTagsFromQuestions();

        return Inertia::render('Admin/QuestionTags', [
            'tags' => QuestionTag::query()
                ->orderBy('name')
                ->get()
                ->map(fn (QuestionTag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'questions_count' => Question::query()
                        ->whereJsonContains('tags', $tag->name)
                        ->count(),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = [
            'name' => trim((string) $request->input('name')),
        ];

        $validated = validator($payload, [
            'name' => 'required|string|max:50|unique:question_tags,name',
        ])->validate();

        QuestionTag::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.question_tags.index');
    }

    public function destroy(QuestionTag $questionTag): RedirectResponse
    {
        $usageCount = Question::query()
            ->whereJsonContains('tags', $questionTag->name)
            ->count();

        if ($usageCount > 0) {
            throw ValidationException::withMessages([
                'delete' => ['当前标签已被题目使用，无法删除。'],
            ]);
        }

        if ($this->isUsedByPaperStrategy($questionTag->name)) {
            throw ValidationException::withMessages([
                'delete' => ['当前标签已被试卷策略使用，无法删除。'],
            ]);
        }

        $questionTag->delete();

        return redirect()->route('admin.question_tags.index');
    }

    private function isUsedByPaperStrategy(string $tagName): bool
    {
        return PaperStrategy::query()
            ->get(['config'])
            ->contains(function (PaperStrategy $strategy) use ($tagName): bool {
                $config = is_array($strategy->config) ? $strategy->config : [];

                $selectedTags = collect($config['tags'] ?? [])
                    ->map(fn ($tag) => trim((string) $tag));

                $ratioTags = collect(is_array($config['tag_ratios'] ?? null) ? array_keys($config['tag_ratios']) : [])
                    ->map(fn ($tag) => trim((string) $tag));

                return $selectedTags->contains($tagName) || $ratioTags->contains($tagName);
            });
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

