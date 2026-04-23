<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\PaperStrategy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
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

        return Inertia::render('Admin/Activities', [
            'activities' => $activities,
            'strategies' => $strategies,
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
        return redirect()->route('admin.activities.index')->with('success', 'Activity created');
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
        return redirect()->route('admin.activities.index')->with('success', 'Activity updated');
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
        return redirect()->route('admin.activities.index')->with('success', 'Activity status updated');
    }
}
