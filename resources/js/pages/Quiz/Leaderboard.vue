<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type LeaderboardRow = {
    user_id: number;
    score: number;
    duration_seconds: number;
    rank: number;
};

const rows = ref<LeaderboardRow[]>([]);
const loading = ref(true);
const error = ref('');

async function loadLeaderboard() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/quiz/activities/1/leaderboard');
        rows.value = Array.isArray(data) ? data : [];
    } catch {
        rows.value = [];
        error.value = 'Unable to load leaderboard.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadLeaderboard);
</script>

<template>
    <div data-testid="leaderboard-page" class="flex h-full flex-col gap-6">
        <section class="space-y-4">
            <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs tracking-[0.2em] text-sky-300/80 uppercase">
                    Ranking board
                </p>
                <h2 class="mt-2 text-2xl font-semibold text-white">
                    Leaderboard
                </h2>
            </div>

            <div
                v-if="loading"
                class="rounded-2xl border border-white/10 bg-white/5 p-4 text-sm text-slate-300"
            >
                Loading leaderboard...
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-400/30 bg-rose-400/10 p-4 text-sm text-rose-100"
            >
                <p class="font-medium">{{ error }}</p>
                <p class="mt-1 text-rose-100/80">
                    Retry will refresh the ranking list.
                </p>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="row in rows"
                    :key="`${row.rank}-${row.user_id}`"
                    class="rounded-2xl border border-white/10 bg-slate-800/90 p-4"
                >
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold text-white">
                                Rank {{ row.rank }}
                            </p>
                            <p class="text-xs text-slate-400">
                                User {{ row.user_id }}
                            </p>
                        </div>
                        <div class="flex gap-4 text-sm text-slate-200">
                            <span>{{ row.score }} pts</span>
                            <span>{{ row.duration_seconds }} s</span>
                        </div>
                    </div>
                </li>
            </ul>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-white/10 bg-slate-900/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="rounded-2xl border border-white/10 px-4 py-4 text-sm font-medium text-slate-200"
                    @click="loadLeaderboard"
                >
                    Retry (Weak Network)
                </button>
                <Link
                    :href="quizRoutes.certificate().url"
                    class="rounded-2xl bg-sky-400 px-4 py-4 text-center text-sm font-semibold text-slate-950"
                >
                    Certificate
                </Link>
            </div>
        </div>
    </div>
</template>
