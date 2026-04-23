<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

type CurrentActivity = {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
};

const loading = ref(true);
const activity = ref<CurrentActivity | null>(null);

const canStart = computed(() => activity.value !== null);
const questionHref = computed(() => {
    const activityId = activity.value?.id;

    if (!activityId) {
        return quizRoutes.question().url;
    }

    return `${quizRoutes.question().url}?activity=${activityId}`;
});

async function loadCurrentActivity(): Promise<void> {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/quiz/activities/current');
        activity.value = (data?.data as CurrentActivity | null) ?? null;
    } catch {
        activity.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(loadCurrentActivity);
</script>

<template>
    <div data-testid="activity-home" class="flex min-h-[70vh] flex-col justify-between gap-6">
        <section class="space-y-4">
            <div
                class="inline-flex rounded-full border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700"
            >
                {{ loading ? 'Loading activity' : canStart ? 'Activity open' : 'No active activity' }}
            </div>
            <div class="space-y-2">
                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">
                    {{ activity?.name || 'Welcome to the challenge' }}
                </h2>
                <p class="text-sm leading-6 text-slate-600">
                    {{
                        activity?.description ||
                        'Answer the quiz question on mobile, then continue to the result, leaderboard, and certificate pages.'
                    }}
                </p>
            </div>

            <div
                class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span>Activity ID</span>
                    <span class="font-medium text-slate-900">{{ activity?.id ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Start</span>
                    <span class="font-medium text-slate-900">{{ activity?.start_date?.slice(0, 10) || '-' }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>End</span>
                    <span class="font-medium text-slate-900">{{ activity?.end_date?.slice(0, 10) || '-' }}</span>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <button
                v-if="!canStart"
                type="button"
                disabled
                class="block w-full rounded-2xl bg-slate-600 px-4 py-4 text-center text-sm font-semibold text-slate-200"
            >
                暂无可参与活动
            </button>
            <Link
                v-else
                :href="questionHref"
                class="block w-full rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white shadow"
            >
                Start quiz
            </Link>
        </div>
    </div>
</template>
