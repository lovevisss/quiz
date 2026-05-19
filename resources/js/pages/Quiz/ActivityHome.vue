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
const activityPeriod = computed(() => {
    if (!activity.value?.start_date && !activity.value?.end_date) {
        return '时间待公布';
    }

    const start = activity.value?.start_date?.slice(0, 10) || '未开始';
    const end = activity.value?.end_date?.slice(0, 10) || '长期开放';

    return `${start} 至 ${end}`;
});
const activityStatusText = computed(() => {
    if (loading.value) {
        return '正在加载活动';
    }

    return canStart.value ? '当前可参与' : '暂无进行中活动';
});
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
    <div data-testid="activity-home" class="flex min-h-[70vh] flex-col justify-between gap-5">
        <section class="space-y-4">
            <div
                class="inline-flex rounded-full border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700"
            >
                {{ activityStatusText }}
            </div>

            <div class="rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-white via-sky-50 to-cyan-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="space-y-2">
                        <p class="text-xs tracking-[0.24em] text-sky-700 uppercase">校园在线答题</p>
                        <h2 class="text-2xl leading-tight font-semibold tracking-tight text-slate-900 sm:text-[1.9rem]">
                            {{ activity?.name || '欢迎参加本期答题活动' }}
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/80 px-3 py-2 text-right shadow-sm">
                        <p class="text-[11px] text-slate-500">参与状态</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ canStart ? '可立即开始' : '敬请期待' }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600 sm:max-w-2xl">
                    {{
                        activity?.description ||
                        '完成答题后可立即查看成绩、排行榜和成就中心，适合手机端随时参与。'
                    }}
                </p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-white/80 bg-white/85 p-3 shadow-sm">
                        <p class="text-[11px] text-slate-500">活动编号</p>
                        <p class="mt-1 text-base font-semibold text-slate-900">{{ activity?.id ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/85 p-3 shadow-sm sm:col-span-2">
                        <p class="text-[11px] text-slate-500">活动时间</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ activityPeriod }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">参与方式</p>
                    <p class="mt-2 text-sm leading-6 text-slate-800">进入答题后逐题作答，完成后自动生成成绩。</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">结果查看</p>
                    <p class="mt-2 text-sm leading-6 text-slate-800">支持查看错题解析、活动排行榜与解锁徽章。</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-medium text-slate-500">移动端体验</p>
                    <p class="mt-2 text-sm leading-6 text-slate-800">底部操作区固定显示，手机上也能顺畅完成答题。</p>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">1. 开始答题</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">2. 提交成绩</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">3. 查看排行</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">4. 解锁成就</span>
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
                class="block w-full rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white shadow transition hover:bg-sky-700"
            >
                开始答题
            </Link>
        </div>
    </div>
</template>
