<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\LeaderboardController;
use App\Models\Activity;
use App\Models\PaperStrategy;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function leaderboard(Activity $activity)
    {
        return Inertia::render('Admin/ActivityLeaderboard', [
            'activity' => $activity->only(['id', 'name', 'start_date', 'end_date']),
            'rows' => LeaderboardController::cachedLeaderboard($activity->id),
        ]);
    }

    public function index()
    {
        $activities = Activity::query()
            ->with('paperStrategy:id,name,mode,status')
            ->orderByDesc('id')
            ->get();

        $strategies = PaperStrategy::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'mode']);

        $currentActivityId = Activity::query()
            ->where('enabled', true)
            ->where(function ($query): void {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->latest('id')
            ->value('id');

        return Inertia::render('Admin/Activities', [
            'activities' => $activities,
            'strategies' => $strategies,
            'currentActivityId' => $currentActivityId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'enabled' => 'sometimes|boolean',
            'paper_strategy_id' => 'nullable|integer|exists:paper_strategies,id',
        ]);
        $validated['enabled'] = (bool) ($validated['enabled'] ?? true);
        $activity = Activity::create($validated);
        Log::info('Admin created activity', [
            'admin_id' => Auth::id(),
            'activity_id' => $activity->id,
            'data' => $validated,
        ]);
        return redirect()->route('admin.activities.index')->with('success', '活动已创建');
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'enabled' => 'sometimes|boolean',
            'paper_strategy_id' => 'nullable|integer|exists:paper_strategies,id',
        ]);
        $activity->update($validated);
        Log::info('Admin updated activity', [
            'admin_id' => Auth::id(),
            'activity_id' => $activity->id,
            'data' => $validated,
        ]);
        return redirect()->route('admin.activities.index')->with('success', '活动已更新');
    }

    public function toggleStatus(Activity $activity)
    {
        $activity->update([
            'enabled' => ! $activity->enabled,
        ]);
        Log::info('Admin toggled activity status', [
            'admin_id' => Auth::id(),
            'activity_id' => $activity->id,
            'enabled' => $activity->enabled,
        ]);
        return redirect()->route('admin.activities.index')->with('success', '活动状态已更新');
    }

    public function destroy(Activity $activity)
    {
        DB::transaction(function () use ($activity): void {
            DB::table('quiz_answers')->whereIn('attempt_id', function ($query) use ($activity): void {
                $query->select('id')->from('quiz_attempts')->where('activity_id', $activity->id);
            })->delete();
            DB::table('quiz_lottery_draws')->where('activity_id', $activity->id)->delete();
            DB::table('quiz_lottery_entries')->where('activity_id', $activity->id)->delete();
            DB::table('quiz_certificates')->where('activity_id', $activity->id)->delete();
            DB::table('survey_responses')->where('activity_id', $activity->id)->delete();
            DB::table('surveys')->where('activity_id', $activity->id)->delete();
            DB::table('quiz_attempts')->where('activity_id', $activity->id)->delete();
            $activity->delete();
        });

        Cache::forget("leaderboard_{$activity->id}");
        Log::info('Admin deleted activity', [
            'admin_id' => Auth::id(),
            'activity_id' => $activity->id,
        ]);

        return redirect()->route('admin.activities.index')->with('success', '活动及关联记录已删除');
    }
}
