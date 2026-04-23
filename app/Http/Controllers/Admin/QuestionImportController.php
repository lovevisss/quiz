<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class QuestionImportController extends Controller
{
    public function import(Request $request, QuestionImportService $importService)
    {
        $request->validate([
            'questions' => 'nullable|array',
            'questions.*.content' => 'required_with:questions|string',
            'questions.*.type' => 'required_with:questions|string',
            'questions.*.options' => 'nullable|array',
            'questions.*.answer' => 'nullable|string',
            'questions.*.explanation' => 'nullable|string',
            'questions.*.option_explanations' => 'nullable|array',
            'questions.*.difficulty' => 'required_with:questions|integer|min:1|max:5',
            'questions.*.tags' => 'nullable|array',
            'questions.*.status' => 'boolean',
            'file' => 'nullable|file|mimes:csv,xlsx',
        ]);

        if ($request->hasFile('file')) {
            $count = $importService->importFile($request->file('file'));
        } elseif (is_array($request->input('questions'))) {
            $count = $importService->import($request->input('questions', []));
        } else {
            throw ValidationException::withMessages([
                'file' => ['Please provide a CSV/XLSX file or questions array.'],
            ]);
        }

        Log::info('Questions imported', ['admin_id' => $request->user()->id, 'count' => $count]);
        return response()->json(['message' => "Imported $count questions"], 201);
    }

    public function template(): Response
    {
        $lines = [
            '序号,题目,选项 A,选项 B,选项 C,选项 D,正确项,解析,标签,类型,难度,启用',
            '1,网络安全中最推荐的密码策略是？,纯数字,字母+数字+符号,生日日期,重复旧密码,B,复杂密码更安全,"security,password",single,1,true',
            '2,收到陌生链接短信应如何处理？,直接点击,转发朋友,官方渠道核实,忽略不管,C,先核实来源避免钓鱼,"security,anti-phishing",single,1,true',
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="question-import-template.csv"',
        ]);
    }
}
