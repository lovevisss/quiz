<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import type { AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    CalendarDays,
    ChevronRight,
    ClipboardCheck,
    Trophy,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type CurrentActivity = {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    paper_strategy?: {
        id: number;
        name: string;
        mode: string;
    } | null;
};

const loading = ref(true);
const activity = ref<CurrentActivity | null>(null);
const error = ref('');
const page = usePage<AppPageProps>();

const canStart = computed(() => Boolean(activity.value));
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const questionHref = computed(() => {
    if (!activity.value) {
        return quizRoutes.question().url;
    }

    return `${quizRoutes.question().url}?activity=${activity.value.id}`;
});
const startHref = computed(() =>
    isAuthenticated.value
        ? questionHref.value
        : `/auth/cas/redirect?return=${encodeURIComponent(questionHref.value)}`,
);

const activityPeriod = computed(() => {
    if (!activity.value?.start_date && !activity.value?.end_date) {
        return '活动时间待公布';
    }

    const start = activity.value.start_date?.slice(0, 10) ?? '未设置开始';
    const end = activity.value.end_date?.slice(0, 10) ?? '长期开放';

    return `${start} 至 ${end}`;
});

async function loadCurrentActivity(): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/quiz/activities/current');
        activity.value = (data?.data as CurrentActivity | null) ?? null;
    } catch {
        activity.value = null;
        error.value = '活动信息加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

onMounted(loadCurrentActivity);
</script>

<template>
    <main
        data-testid="activity-home"
        class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-3xl flex-col px-4 pt-4 pb-28 sm:px-6"
    >
        <section class="space-y-5">
            <div
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-emerald-700 uppercase"
                        >
                            在线答题活动
                        </p>
                        <h1
                            class="mt-3 text-2xl leading-tight font-bold text-slate-950"
                        >
                            {{ activity?.name || '欢迎参加本期答题活动' }}
                        </h1>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            {{
                                activity?.description ||
                                '完成答题后可查看成绩、错题解析、排行榜和证书。界面已针对手机端操作优化。'
                            }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold"
                        :class="
                            canStart
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-slate-100 text-slate-600'
                        "
                    >
                        {{
                            loading
                                ? '加载中'
                                : canStart
                                  ? '可参与'
                                  : '暂无活动'
                        }}
                    </span>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <CalendarDays class="h-5 w-5 text-emerald-700" />
                        <p class="mt-3 text-xs text-slate-500">活动时间</p>
                        <p
                            class="mt-1 text-sm leading-5 font-semibold text-slate-900"
                        >
                            {{ activityPeriod }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <ClipboardCheck class="h-5 w-5 text-sky-700" />
                        <p class="mt-3 text-xs text-slate-500">组卷策略</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{
                                activity?.paper_strategy?.name || '默认随机题组'
                            }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <Trophy class="h-5 w-5 text-amber-600" />
                        <p class="mt-3 text-xs text-slate-500">完成后</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            成绩、排行榜、证书
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="error"
                class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
            >
                {{ error }}
            </div>

            <div
                v-else-if="!loading && !activity"
                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm leading-6 text-slate-600"
            >
                当前没有正在进行的活动。请关注活动开放时间，或联系管理员启用活动。
            </div>

            <div class="grid gap-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">答题流程</p>
                    <div class="mt-3 grid gap-2 text-sm text-slate-600">
                        <p>1. 进入答题页后逐题作答。</p>
                        <p>2. 系统自动保存已答题目。</p>
                        <p>3. 提交后查看分数和错题解析。</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4">
                    <p class="text-sm font-semibold text-slate-900">
                        手机端提示
                    </p>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        底部按钮会固定在屏幕下方，答题时不用反复滚动；选项区域采用大触控目标，适合单手操作。
                    </p>
                </div>
            </div>
        </section>

        <div
            class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-10px_30px_-24px_rgba(15,23,42,0.5)] backdrop-blur"
        >
            <div class="mx-auto max-w-3xl pb-[env(safe-area-inset-bottom)]">
                <button
                    v-if="!canStart"
                    type="button"
                    disabled
                    class="flex w-full items-center justify-center rounded-2xl bg-slate-300 px-4 py-4 text-sm font-semibold text-slate-600"
                >
                    {{ loading ? '正在加载活动...' : '暂无可参与活动' }}
                </button>
                <Link
                    v-else
                    :href="startHref"
                    class="flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-4 text-sm font-semibold text-white shadow-sm transition active:scale-[0.99]"
                >
                    {{ isAuthenticated ? '开始答题' : '登录后开始答题' }}
                    <ChevronRight class="h-4 w-4" />
                </Link>
            </div>
        </div>
    </main>
</template>
