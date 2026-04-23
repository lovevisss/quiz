<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class QuestionController extends Controller
{
    public function index()
    {
        $questions = Question::orderByDesc('id')->get();
        return Inertia::render('Admin/Questions', [
            'questions' => $questions,
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
            'status' => 'nullable|boolean',
        ]);
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
            'status' => 'nullable|boolean',
        ]);
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
}
