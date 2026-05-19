<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import {
    BrainCircuit,
    CalendarCheck2,
    Crown,
    Flag,
    Rocket,
    Sparkles,
    Star,
    Trophy,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type Achievement = {
    key: string;
    title: string;
    description: string;
    unlocked: boolean;
    progress: string;
    progress_meta?: {
        current: number | null;
        target: number;
        percent: number;
        unit: string;
        direction: 'up' | 'down';
    };
    icon: string;
    tone: string;
    badge_label: string;
};

type Summary = {
    submitted_attempts: number;
    perfect_attempts: number;
    correct_answers: number;
    fastest_correct_seconds: number | null;
    max_correct_streak: number;
    max_fast_streak: number;
    fastest_perfect_run_seconds: number | null;
    unlocked_count: number;
};

const loading = ref(true);
const error = ref('');
const achievements = ref<Achievement[]>([]);
const summary = ref<Summary>({
    submitted_attempts: 0,
    perfect_attempts: 0,
    correct_answers: 0,
    fastest_correct_seconds: null,
    max_correct_streak: 0,
    max_fast_streak: 0,
    fastest_perfect_run_seconds: null,
    unlocked_count: 0,
});

const unlockedAchievements = computed(() => achievements.value.filter((item) => item.unlocked));

const iconMap = {
    'brain-circuit': BrainCircuit,
    'calendar-check-2': CalendarCheck2,
    crown: Crown,
    flag: Flag,
    rocket: Rocket,
    sparkles: Sparkles,
    star: Star,
    trophy: Trophy,
    zap: Zap,
} as const;

function iconComponent(name: string) {
    return iconMap[name as keyof typeof iconMap] ?? Sparkles;
}

function toneClasses(tone: string, unlocked: boolean): string {
    const palette: Record<string, string> = {
        sky: unlocked ? 'from-sky-100 via-white to-cyan-100 border-sky-200' : 'from-slate-50 to-white border-slate-200',
        indigo: unlocked ? 'from-indigo-100 via-white to-violet-100 border-indigo-200' : 'from-slate-50 to-white border-slate-200',
        amber: unlocked ? 'from-amber-100 via-white to-yellow-100 border-amber-200' : 'from-slate-50 to-white border-slate-200',
        emerald: unlocked ? 'from-emerald-100 via-white to-lime-100 border-emerald-200' : 'from-slate-50 to-white border-slate-200',
        yellow: unlocked ? 'from-yellow-100 via-white to-amber-100 border-yellow-200' : 'from-slate-50 to-white border-slate-200',
        violet: unlocked ? 'from-violet-100 via-white to-fuchsia-100 border-violet-200' : 'from-slate-50 to-white border-slate-200',
        rose: unlocked ? 'from-rose-100 via-white to-orange-100 border-rose-200' : 'from-slate-50 to-white border-slate-200',
        orange: unlocked ? 'from-orange-100 via-white to-amber-100 border-orange-200' : 'from-slate-50 to-white border-slate-200',
        fuchsia: unlocked ? 'from-fuchsia-100 via-white to-pink-100 border-fuchsia-200' : 'from-slate-50 to-white border-slate-200',
    };

    return palette[tone] ?? palette.sky;
}

function iconToneClass(tone: string, unlocked: boolean): string {
    const palette: Record<string, string> = {
        sky: unlocked ? 'bg-sky-600 text-white' : 'bg-slate-200 text-slate-500',
        indigo: unlocked ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-500',
        amber: unlocked ? 'bg-amber-500 text-white' : 'bg-slate-200 text-slate-500',
        emerald: unlocked ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500',
        yellow: unlocked ? 'bg-yellow-500 text-white' : 'bg-slate-200 text-slate-500',
        violet: unlocked ? 'bg-violet-600 text-white' : 'bg-slate-200 text-slate-500',
        rose: unlocked ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-500',
        orange: unlocked ? 'bg-orange-500 text-white' : 'bg-slate-200 text-slate-500',
        fuchsia: unlocked ? 'bg-fuchsia-600 text-white' : 'bg-slate-200 text-slate-500',
    };

    return palette[tone] ?? palette.sky;
}

function summaryTime(value: number | null): string {
    if (value === null || value === undefined) {
        return '暂无';
    }

    return `${value} 秒`;
}

function progressPercent(achievement: Achievement): number {
    const percent = achievement.progress_meta?.percent;

    if (typeof percent !== 'number' || Number.isNaN(percent)) {
        return achievement.unlocked ? 100 : 0;
    }

    return Math.max(0, Math.min(100, percent));
}

async function loadAchievements(): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/quiz/achievements');
        achievements.value = Array.isArray(data?.data) ? data.data : [];
        summary.value = {
            submitted_attempts: Number(data?.summary?.submitted_attempts ?? 0),
            perfect_attempts: Number(data?.summary?.perfect_attempts ?? 0),
            correct_answers: Number(data?.summary?.correct_answers ?? 0),
            fastest_correct_seconds:
                data?.summary?.fastest_correct_seconds === null || data?.summary?.fastest_correct_seconds === undefined
                    ? null
                    : Number(data.summary.fastest_correct_seconds),
            max_correct_streak: Number(data?.summary?.max_correct_streak ?? 0),
            max_fast_streak: Number(data?.summary?.max_fast_streak ?? 0),
            fastest_perfect_run_seconds:
                data?.summary?.fastest_perfect_run_seconds === null || data?.summary?.fastest_perfect_run_seconds === undefined
                    ? null
                    : Number(data.summary.fastest_perfect_run_seconds),
            unlocked_count: Number(data?.summary?.unlocked_count ?? 0),
        };
    } catch {
        achievements.value = [];
        error.value = '成就数据加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

onMounted(loadAchievements);
</script>

<template>
    <div
        data-testid="certificate-page"
        class="flex h-full flex-col justify-between gap-5"
    >
        <section class="space-y-4">
            <div
                class="rounded-[1.75rem] border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-sky-50 p-5 text-slate-900 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-amber-600 uppercase">成就中心</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-tight">
                            我的答题成就
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/80 px-3 py-2 text-right shadow-sm">
                        <p class="text-[11px] text-slate-500">当前已解锁</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ summary.unlocked_count }} 枚徽章</p>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    这里展示你在答题过程中的关键里程碑、速度徽章与连击成就。
                </p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <div class="rounded-full bg-white/80 px-4 py-2 text-sm font-medium text-slate-700 shadow-sm">
                        已解锁 {{ summary.unlocked_count }} 枚徽章
                    </div>
                    <div class="rounded-full bg-amber-100 px-4 py-2 text-sm font-medium text-amber-700 shadow-sm">
                        满分 {{ summary.perfect_attempts }} 次
                    </div>
                </div>
            </div>

            <div v-if="loading" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                正在加载成就...
            </div>

            <div v-else-if="error" class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-700">
                {{ error }}
            </div>

            <div v-else class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">累计完成答题</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ summary.submitted_attempts }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">满分次数</p>
                        <p class="mt-2 text-3xl font-bold text-amber-500">{{ summary.perfect_attempts }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">最快单题正确</p>
                        <p class="mt-2 text-3xl font-bold text-sky-600">{{ summaryTime(summary.fastest_correct_seconds) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">最长连对</p>
                        <p class="mt-2 text-3xl font-bold text-fuchsia-600">{{ summary.max_correct_streak }}</p>
                    </div>
                </div>

                <div
                    v-if="unlockedAchievements.length > 0"
                    class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-sky-50 p-5 shadow-sm"
                >
                    <p class="text-xs tracking-[0.2em] text-emerald-600 uppercase">荣耀展柜</p>
                    <p class="mt-2 text-sm text-slate-600">优先展示你最近已具备代表性的核心徽章。</p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <div
                            v-for="achievement in unlockedAchievements.slice(0, 4)"
                            :key="`featured-${achievement.key}`"
                            class="achievement-chip flex items-center gap-3 rounded-2xl border border-white/70 bg-white/80 px-4 py-3 shadow-sm"
                        >
                            <span :class="['flex h-10 w-10 items-center justify-center rounded-2xl', iconToneClass(achievement.tone, true)]">
                                <component :is="iconComponent(achievement.icon)" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ achievement.title }}</p>
                                <p class="text-xs text-slate-500">{{ achievement.badge_label }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(achievement, index) in achievements"
                        :key="achievement.key"
                        class="achievement-card relative overflow-hidden rounded-3xl border bg-gradient-to-br p-5 shadow-sm transition-transform duration-300 hover:-translate-y-1"
                        :class="[
                            toneClasses(achievement.tone, achievement.unlocked),
                        ]"
                        :data-state="achievement.unlocked ? 'unlocked' : 'locked'"
                        :style="{ animationDelay: `${index * 90}ms` }"
                    >
                        <div class="flex flex-col gap-4">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex items-start gap-4">
                                    <span
                                        :class="[
                                            'flex h-14 w-14 shrink-0 items-center justify-center rounded-3xl shadow-sm transition-transform duration-300',
                                            iconToneClass(achievement.tone, achievement.unlocked),
                                        ]"
                                    >
                                        <component :is="iconComponent(achievement.icon)" class="h-7 w-7" />
                                    </span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-lg font-semibold text-slate-900">{{ achievement.title }}</p>
                                            <span class="rounded-full bg-white/70 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                                {{ achievement.badge_label }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-slate-600">{{ achievement.description }}</p>
                                    </div>
                                </div>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="achievement.unlocked ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                >
                                    {{ achievement.unlocked ? '已解锁' : '未解锁' }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-center justify-between text-xs text-slate-500">
                                <span>当前进度</span>
                                <span>{{ achievement.progress }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/70">
                                <div
                                    class="h-full rounded-full bg-slate-900/70 transition-all duration-700"
                                    :class="achievement.unlocked ? 'opacity-100' : 'opacity-60'"
                                    :style="{ width: `${progressPercent(achievement)}%` }"
                                ></div>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-sm font-medium text-slate-700"
                    @click="loadAchievements"
                >
                    刷新成就
                </button>
                <Link
                    :href="quizRoutes.index().url"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    返回首页
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.achievement-card {
    animation: badge-rise 0.7s ease both;
}

.achievement-card[data-state='unlocked'] {
    box-shadow: 0 16px 40px -24px rgba(16, 185, 129, 0.7);
}

.achievement-card[data-state='unlocked']::after {
    content: '';
    position: absolute;
    inset: 1px;
    border-radius: 1.5rem;
    background: linear-gradient(120deg, rgba(255, 255, 255, 0.14), transparent 32%, rgba(255, 255, 255, 0.26) 60%, transparent 80%);
    transform: translateX(-120%);
    animation: badge-shine 3.2s ease-in-out infinite;
    pointer-events: none;
}

.achievement-card[data-state='locked'] {
    opacity: 0.9;
}

.achievement-chip {
    animation: badge-pulse 1.8s ease-in-out infinite;
}

@keyframes badge-rise {
    from {
        opacity: 0;
        transform: translateY(16px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes badge-shine {
    0% {
        transform: translateX(-120%);
    }

    45%, 100% {
        transform: translateX(120%);
    }
}

@keyframes badge-pulse {
    0%, 100% {
        transform: translateY(0);
    }

    50% {
        transform: translateY(-2px);
    }
}
</style>

