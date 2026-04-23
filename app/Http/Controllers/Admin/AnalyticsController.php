<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use App\Models\QuizCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(Request $request)
    {
        // 参与人数
        $participants = QuizAttempt::distinct('user_id')->count('user_id');
        // 完赛率（有提交的/总参与）
        $total = QuizAttempt::count();
        $completed = QuizAttempt::whereNotNull('submitted_at')->count();
        $completion_rate = $total > 0 ? round($completed / $total, 4) : 0;
        // 平均分
        $average_score = QuizAttempt::whereNotNull('score')->avg('score') ?? 0;
        // 题目正确率（近似：所有得分/满分*题数）
        // 这里只做最小实现，假设满分为100
        $correct_rate = $average_score / 100;
        return response()->json([
            'participants' => $participants,
            'completion_rate' => $completion_rate,
            'average_score' => round($average_score, 2),
            'correct_rate' => round($correct_rate, 4),
        ]);
    }
}
