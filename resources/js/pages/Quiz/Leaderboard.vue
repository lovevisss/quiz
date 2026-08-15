<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { Award, Medal, RefreshCw, Trophy } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type LeaderboardRow = {
    user_id: number;
    user_name?: string;
    score: number;
    duration_seconds: number | null;
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
const currentUserRow = computed(
    () => rows.value.find((row) => row.is_current_user) ?? null,
);

function resolveActivityIdFromQuery(): number | null {
    const raw = Number(
        new URLSearchParams(window.location.search).get('activity'),
    );
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

function formatDuration(seconds: number | null): string {
    if (!Number.isFinite(seconds ?? 0) || !seconds || seconds <= 0) {
        return '未记录';
    }

    const minutes = Math.floor(seconds / 60);
    const remainSeconds = seconds % 60;

    return `${minutes}分${String(remainSeconds).padStart(2, '0')}秒`;
}

function rankIcon(rank: number) {
    if (rank === 1) {
        return Trophy;
    }

    return rank === 2 ? Medal : Award;
}

function rankClass(rank: number): string {
    if (rank === 1) {
        return 'border-amber-300 bg-amber-50 text-amber-700';
    }

    if (rank === 2) {
        return 'border-slate-300 bg-slate-50 text-slate-700';
    }

    return 'border-orange-200 bg-orange-50 text-orange-700';
}

async function loadLeaderboard(): Promise<void> {
    loading.value = true;
    error.value = '';

    try {
        activityId.value = await resolveActivityId();
        if (!activityId.value) {
            rows.value = [];
            error.value = '当前没有可查看排行的活动。';
            return;
        }

        const { data } = await axios.get(
            `/api/quiz/activities/${activityId.value}/leaderboard`,
        );
        rows.value = Array.isArray(data) ? data : [];
    } catch {
        rows.value = [];
        error.value = '排行榜加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

onMounted(loadLeaderboard);
</script>

<template>
    <main
        data-testid="leaderboard-page"
        class="mx-auto min-h-screen max-w-4xl px-4 pt-4 pb-28 sm:px-6"
    >
        <section class="space-y-4">
            <div
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-emerald-700 uppercase"
                        >
                            活动排行榜
                        </p>
                        <h1 class="mt-3 text-2xl font-bold text-slate-950">
                            成绩排名
                        </h1>
                        <p class="mt-2 text-sm text-slate-500">
                            {{
                                activityId
                                    ? `活动 #${activityId}`
                                    : '根据当前活动展示排名'
                            }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 px-4 py-3 text-right">
                        <p class="text-xs text-slate-500">上榜人数</p>
                        <p class="mt-1 text-2xl font-bold text-slate-950">
                            {{ rows.length }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="currentUserRow"
                    class="mt-4 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800"
                >
                    我的排名：第 {{ currentUserRow.rank }} 名，得分
                    {{ currentUserRow.score }}。
                </div>
            </div>

            <div
                v-if="loading"
                class="rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600"
            >
                正在加载排行榜...
            </div>

            <div
                v-else-if="error"
                class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700"
            >
                {{ error }}
            </div>

            <div
                v-else-if="rows.length === 0"
                class="rounded-3xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-600"
            >
                当前活动暂时没有可展示的成绩记录。
            </div>

            <template v-else>
                <section class="grid gap-3 md:grid-cols-3">
                    <article
                        v-for="row in topThree"
                        :key="`top-${row.rank}-${row.user_id}`"
                        class="rounded-3xl border p-5 shadow-sm"
                        :class="rankClass(row.rank)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <component
                                :is="rankIcon(row.rank)"
                                class="h-8 w-8"
                            />
                            <span
                                v-if="row.is_current_user"
                                class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold"
                                >我</span
                            >
                        </div>
                        <p
                            class="mt-4 text-xs font-semibold tracking-[0.18em] uppercase"
                        >
                            第 {{ row.rank }} 名
                        </p>
                        <p
                            class="mt-2 truncate text-lg font-bold text-slate-950"
                        >
                            {{ row.user_name || `用户 ${row.user_id}` }}
                        </p>
                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-2xl bg-white/80 p-3">
                                <p class="text-xs text-slate-500">得分</p>
                                <p
                                    class="mt-1 text-xl font-bold text-slate-950"
                                >
                                    {{ row.score }}
                                </p>
                            </div>
                            <div class="rounded-2xl bg-white/80 p-3">
                                <p class="text-xs text-slate-500">用时</p>
                                <p
                                    class="mt-1 text-sm font-bold text-slate-950"
                                >
                                    {{ formatDuration(row.duration_seconds) }}
                                </p>
                            </div>
                        </div>
                    </article>
                </section>

                <section
                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <h2 class="px-1 text-lg font-bold text-slate-950">
                        更多排名
                    </h2>
                    <ul class="mt-3 divide-y divide-slate-100">
                        <li
                            v-for="row in otherRows"
                            :key="`${row.rank}-${row.user_id}`"
                            class="flex items-center justify-between gap-3 py-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-sm font-bold text-slate-700"
                                >
                                    {{ row.rank }}
                                </span>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold text-slate-950"
                                    >
                                        {{
                                            row.user_name ||
                                            `用户 ${row.user_id}`
                                        }}
                                        <span
                                            v-if="row.is_current_user"
                                            class="ml-1 text-emerald-700"
                                            >我</span
                                        >
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{
                                            formatDuration(row.duration_seconds)
                                        }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="shrink-0 text-sm font-bold text-slate-950"
                                >{{ row.score }} 分</span
                            >
                        </li>
                    </ul>
                </section>
            </template>
        </section>

        <footer
            class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur"
        >
            <div
                class="mx-auto grid max-w-4xl grid-cols-2 gap-3 pb-[env(safe-area-inset-bottom)]"
            >
                <button
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-4 text-sm font-semibold text-slate-700"
                    @click="loadLeaderboard"
                >
                    <RefreshCw class="h-4 w-4" />
                    重新加载
                </button>
                <Link
                    :href="quizRoutes.certificate().url"
                    class="rounded-2xl bg-emerald-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    查看证书
                </Link>
            </div>
        </footer>
    </main>
</template>
