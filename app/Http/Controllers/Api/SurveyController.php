<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\QuizAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function current(Request $request, Activity $activity): JsonResponse
    {
        $survey = Survey::where('activity_id', $activity->id)->first();
        if (! $survey) {
            return response()->json(['error' => 'survey_not_found'], 404);
        }
        return response()->json([
            'survey_id' => $survey->id,
            'title' => $survey->title,
            'structure' => $survey->structure_json,
        ]);
    }

    public function submit(Request $request, Activity $activity): JsonResponse
    {
        $user = $request->user();
        $survey = Survey::where('activity_id', $activity->id)->first();
        if (! $survey) {
            return response()->json(['reason' => 'survey_not_found'], 404);
        }
        // 检查是否已完成答题
        $attempt = QuizAttempt::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();
        if (! $attempt) {
            return response()->json(['reason' => 'quiz_not_completed'], 403);
        }
        // 检查是否重复提交
        $existing = SurveyResponse::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return response()->json([
                'reason' => 'duplicate_submission',
                'submitted' => true,
                'response_id' => $existing->id,
                'lottery_eligible' => $this->lotteryEligible($activity, $user),
            ], 409);
        }
        // 创建问卷响应
        $response = SurveyResponse::create([
            'survey_id' => $survey->id,
            'activity_id' => $activity->id,
            'user_id' => $user->id,
            'response_json' => $request->input('response', []),
        ]);
        return response()->json([
            'submitted' => true,
            'response_id' => $response->id,
            'lottery_eligible' => $this->lotteryEligible($activity, $user),
        ]);
    }

    private function lotteryEligible(Activity $activity, $user): bool
    {
        // 复用抽奖资格判定逻辑（与 QuizLotteryController 保持一致）
        $attempt = QuizAttempt::where('activity_id', $activity->id)
            ->where('user_id', $user->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->first();
        if (! $attempt) {
            return false;
        }
        // 这里可根据业务需要扩展更复杂的资格判定
        return true;
    }
}
