<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SurveyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SurveyTemplateController extends Controller
{
    public function index()
    {
        $templates = SurveyTemplate::latest()->paginate(20);
        return Inertia::render('Admin/SurveyTemplates', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/SurveyTemplateForm');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $template = SurveyTemplate::create($data);
        Log::info('SurveyTemplate created', ['id' => $template->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.survey_templates.index');
    }

    public function edit(SurveyTemplate $survey_template)
    {
        return Inertia::render('Admin/SurveyTemplateForm', [
            'template' => $survey_template,
        ]);
    }

    public function update(Request $request, SurveyTemplate $survey_template)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'boolean',
        ]);
        $survey_template->update($data);
        Log::info('SurveyTemplate updated', ['id' => $survey_template->id, 'admin_id' => $request->user()->id]);
        return redirect()->route('admin.survey_templates.index');
    }

    public function destroy(SurveyTemplate $survey_template)
    {
        $survey_template->delete();
        Log::info('SurveyTemplate deleted', ['id' => $survey_template->id, 'admin_id' => auth()->id()]);
        return redirect()->route('admin.survey_templates.index');
    }
}
