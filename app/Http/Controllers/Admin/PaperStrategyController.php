<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaperStrategy;
use App\Models\Question;
use App\Models\QuestionTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaperStrategyController extends Controller
{
    public function index()
    {
        $strategies = PaperStrategy::latest()->paginate(20);
        return Inertia::render('Admin/PaperStrategies', [
            'strategies' => $strategies,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/PaperStrategyForm', $this->formProps());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'mode' => 'required|in:fixed,random',
            'config' => 'nullable|array',
            'status' => 'boolean',
        ]);
        $strategy = PaperStrategy::create($data);
        Log::info('PaperStrategy created', ['id' => $strategy->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.paper_strategies.index');
    }

    public function edit(PaperStrategy $paper_strategy)
    {
        return Inertia::render('Admin/PaperStrategyForm', [
            'strategy' => $paper_strategy,
            ...$this->formProps(),
        ]);
    }

    public function update(Request $request, PaperStrategy $paper_strategy)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'mode' => 'required|in:fixed,random',
            'config' => 'nullable|array',
            'status' => 'boolean',
        ]);
        $paper_strategy->update($data);
        Log::info('PaperStrategy updated', ['id' => $paper_strategy->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.paper_strategies.index');
    }

    public function destroy(PaperStrategy $paper_strategy)
    {
        $paper_strategy->delete();
        Log::info('PaperStrategy deleted', ['id' => $paper_strategy->id, 'admin_id' => auth()->id()]);
        return redirect()->route('admin.paper_strategies.index');
    }

    private function formProps(): array
    {
        return [
            'questions' => Question::query()
                ->where('status', true)
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'content', 'type', 'tags']),
            'tags' => QuestionTag::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
