<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyQuestion;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SurveyQuestionController extends Controller
{
    public function index(SurveyTemplate $survey_template)
    {
        $questions = $survey_template->questions()->orderBy('order')->get();
        return Inertia::render('Admin/SurveyQuestions', [
            'questions' => $questions,
            'template' => $survey_template,
        ]);
    }

    public function create(SurveyTemplate $survey_template)
    {
        return Inertia::render('Admin/SurveyQuestionForm', [
            'template' => $survey_template,
        ]);
    }

    public function store(Request $request, SurveyTemplate $survey_template)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:single,multiple,multi,text',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'order' => 'integer',
        ]);
        $data['survey_template_id'] = $survey_template->id;
        $question = SurveyQuestion::create($data);
        Log::info('SurveyQuestion created', ['id' => $question->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.survey_questions.index', $survey_template);
    }

    public function edit(SurveyTemplate $survey_template, SurveyQuestion $survey_question)
    {
        return Inertia::render('Admin/SurveyQuestionForm', [
            'template' => $survey_template,
            'question' => $survey_question,
        ]);
    }

    public function update(Request $request, SurveyTemplate $survey_template, SurveyQuestion $survey_question)
    {
        $data = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:single,multiple,multi,text',
            'options' => 'nullable|array',
            'required' => 'boolean',
            'order' => 'integer',
        ]);
        $survey_question->update($data);
        Log::info('SurveyQuestion updated', ['id' => $survey_question->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.survey_questions.index', $survey_template);
    }

    public function destroy(SurveyTemplate $survey_template, SurveyQuestion $survey_question)
    {
        $survey_question->delete();
        Log::info('SurveyQuestion deleted', ['id' => $survey_question->id, 'admin_id' => auth()->id()]);
        return redirect()->route('admin.survey_questions.index', $survey_template);
    }
}
