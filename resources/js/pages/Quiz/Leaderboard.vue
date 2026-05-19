<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { Award, Crown, Medal } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type LeaderboardRow = {
    user_id: number;
    user_name?: string;
    score: number;
    duration_seconds: number;
    rank: number;
    submitted_at?: string;
    is_current_user?: boolean;
};

const rows = ref<LeaderboardRow[]>([]);
const loading = ref(true);
const error = ref('');
const activityId = ref<number | null>(null);

const topThree = computed(() => rows.value.slice(0, 3));
const otherRows = computed(() => rows.value.slice(3));
const currentUserRow = computed(() => rows.value.find((row) => row.is_current_user) ?? null);

function topRankCardClass(rank: number): string {
    if (rank === 1) {
        return 'border-amber-300 bg-gradient-to-br from-amber-50 via-white to-yellow-50 shadow-[0_20px_50px_-28px_rgba(245,158,11,0.8)]';
    }

    if (rank === 2) {
        return 'border-slate-300 bg-gradient-to-br from-slate-100 via-white to-slate-50 shadow-[0_18px_44px_-30px_rgba(100,116,139,0.55)]';
    }

    return 'border-orange-200 bg-gradient-to-br from-orange-50 via-white to-amber-50 shadow-[0_18px_44px_-30px_rgba(249,115,22,0.45)]';
}

function topRankIcon(rank: number) {
    if (rank === 1) {
        return Crown;
    }

    return rank === 2 ? Medal : Award;
}

function topRankIconClass(rank: number): string {
    if (rank === 1) {
        return 'bg-amber-500 text-white';
    }

    if (rank === 2) {
        return 'bg-slate-500 text-white';
    }

    return 'bg-orange-500 text-white';
}

function topRankLabel(rank: number): string {
    if (rank === 1) {
        return '冠军';
    }

    if (rank === 2) {
        return '亚军';
    }

    return '季军';
}

function resolveActivityIdFromQuery(): number | null {
    const raw = Number(new URLSearchParams(window.location.search).get('activity'));

    return Number.isInteger(raw) && raw > 0 ? raw : null;
}

async function resolveActivityId(): Promise<number | null> {
    const fromQuery = resolveActivityIdFromQuery();

    if (fromQuery) {
        return fromQuery;
    }

    try {
        const { data } = await axios.get('/api/quiz/activities/current');

        return data?.data?.id ? Number(data.data.id) : null;
    } catch {
        return null;
    }
}

function formatDuration(seconds: number): string {
    if (!Number.isFinite(seconds) || seconds <= 0) {
        return '未记录';
    }

    const minutes = Math.floor(seconds / 60);
    const remainSeconds = seconds % 60;

    return `${minutes}分${String(remainSeconds).padStart(2, '0')}秒`;
}

async function loadLeaderboard() {
    loading.value = true;
    error.value = '';

    try {
        activityId.value = await resolveActivityId();

        if (!activityId.value) {
            rows.value = [];
            error.value = '当前没有可查看排行的活动。';
            return;
        }

        const { data } = await axios.get(`/api/quiz/activities/${activityId.value}/leaderboard`);
        rows.value = Array.isArray(data) ? data : [];
    } catch {
        rows.value = [];
        error.value = '排行榜加载失败，请稍后再试。';
    } finally {
        loading.value = false;
    }
}

onMounted(loadLeaderboard);
</script>

<template>
    <div data-testid="leaderboard-page" class="flex h-full flex-col gap-5">
        <section class="space-y-4">
            <div class="rounded-[1.75rem] border border-sky-200 bg-gradient-to-br from-sky-50 via-white to-indigo-50 p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-sky-600 uppercase">
                            排行榜
                        </p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                            活动排行榜
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/80 px-3 py-2 text-right shadow-sm">
                        <p class="text-[11px] text-slate-500">当前活动</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ activityId ? `#${activityId}` : '未识别' }}
                        </p>
                    </div>
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    {{ activityId ? `当前查看活动 #${activityId} 的成绩排名` : '根据当前活动展示成绩排名' }}
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-slate-700">
                        已上榜 {{ rows.length }} 人
                    </span>
                    <span v-if="currentUserRow" class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">
                        我的排名 第 {{ currentUserRow.rank }} 名
                    </span>
                </div>
            </div>

            <div
                v-if="loading"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                正在加载排行榜...
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-700"
            >
                <p class="font-medium">{{ error }}</p>
                <p class="mt-1 text-rose-700/80">点击下方“重新加载”可再次尝试。</p>
            </div>

            <div v-else-if="rows.length === 0" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                该活动暂时还没有可显示的成绩记录。
            </div>

            <div v-else class="space-y-4">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">上榜人数</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900">{{ rows.length }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">冠军得分</p>
                        <p class="mt-2 text-3xl font-bold text-amber-500">{{ rows[0]?.score ?? '-' }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">冠军用时</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ formatDuration(rows[0]?.duration_seconds ?? 0) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs text-slate-500">我的排名</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">
                            {{ currentUserRow ? `第 ${currentUserRow.rank} 名` : '暂未上榜' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-3">
                    <div
                        v-for="row in topThree"
                        :key="`top-${row.rank}-${row.user_id}`"
                        class="relative overflow-hidden rounded-3xl border p-5 shadow-sm"
                        :class="[
                            topRankCardClass(row.rank),
                            row.rank === 1 ? 'md:-translate-y-2' : '',
                        ]"
                    >
                        <div class="absolute top-0 right-0 h-20 w-20 rounded-full bg-white/40 blur-2xl"></div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span
                                    :class="['flex h-12 w-12 shrink-0 items-center justify-center rounded-3xl shadow-sm', topRankIconClass(row.rank)]"
                                >
                                    <component :is="topRankIcon(row.rank)" class="h-6 w-6" />
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p
                                            class="text-xs font-medium tracking-[0.2em] uppercase"
                                            :class="row.rank === 1 ? 'text-amber-600' : row.rank === 2 ? 'text-slate-500' : 'text-orange-500'"
                                        >
                                            {{ topRankLabel(row.rank) }}
                                        </p>
                                        <span class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                            第 {{ row.rank }} 名
                                        </span>
                                    </div>
                                    <p class="mt-3 text-lg font-semibold text-slate-900">{{ row.user_name || `用户 ${row.user_id}` }}</p>
                                </div>
                            </div>
                            <span
                                v-if="row.is_current_user"
                                class="rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-medium text-sky-700"
                            >
                                我
                            </span>
                        </div>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-2xl bg-white/80 p-3 shadow-sm">
                                <p class="text-[11px] text-slate-500">得分</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ row.score }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/80 p-3 shadow-sm">
                                <p class="text-[11px] text-slate-500">用时</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ formatDuration(row.duration_seconds) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <ul class="space-y-3">
                <li
                    v-for="row in otherRows"
                    :key="`${row.rank}-${row.user_id}`"
                    class="rounded-2xl border bg-white p-4 shadow-sm"
                    :class="row.is_current_user ? 'border-sky-300 ring-2 ring-sky-100' : 'border-slate-200'"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                第 {{ row.rank }} 名
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ row.user_name || `用户 ${row.user_id}` }}
                            </p>
                        </div>
                        <div class="flex gap-4 text-sm text-slate-600">
                            <span>得分 {{ row.score }}</span>
                            <span>用时 {{ formatDuration(row.duration_seconds) }}</span>
                        </div>
                    </div>
                </li>
                </ul>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-sm font-medium text-slate-700"
                    @click="loadLeaderboard"
                >
                    重新加载
                </button>
                <Link
                    :href="quizRoutes.certificate().url"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    查看成就
                </Link>
            </div>
        </div>
    </div>
</template>
