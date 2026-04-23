<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

type ResultState = {
    score: number;
    submitted_at: string | null;
    wrong_questions: Array<{
        question_id: number;
        content: string | null;
        your_answer: unknown;
        correct_answer: string | null;
        explanation: string | null;
        option_explanations: Record<string, string>;
    }>;
};

const loading = ref(true);
const error = ref('');
const result = ref<ResultState | null>(null);

function resolveAttemptId(): number | null {
    const raw = Number(new URLSearchParams(window.location.search).get('attempt'));

    if (!Number.isInteger(raw) || raw <= 0) {
        return null;
    }

    return raw;
}

async function loadResult() {
    loading.value = true;
    error.value = '';

    const attemptId = resolveAttemptId();
    if (!attemptId) {
        result.value = null;
        error.value = 'Missing attempt id. Please finish quiz from the question page.';
        loading.value = false;

        return;
    }

    try {
        const { data } = await axios.get(`/api/quiz/attempts/${attemptId}/result`);
        result.value = {
            score: Number(data.score ?? 0),
            submitted_at: data.submitted_at ?? null,
            wrong_questions: Array.isArray(data.wrong_questions)
                ? data.wrong_questions
                : [],
        };
    } catch {
        result.value = null;
        error.value = 'Unable to load the result right now.';
    } finally {
        loading.value = false;
    }
}

onMounted(loadResult);
</script>

<template>
    <div
        data-testid="quiz-result"
        class="flex h-full flex-col gap-6"
        style="padding-top: 20px"
    >
        <section class="space-y-4">
            <div
                class="rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800"
            >
                <p
                    class="text-xs tracking-[0.2em] text-emerald-700 uppercase"
                >
                    Submission complete
                </p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                    Your score summary
                </h2>
            </div>

            <div
                v-if="loading"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                Loading result...
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-700"
            >
                <p class="font-medium">{{ error }}</p>
                <p class="mt-1 text-rose-700/80">
                    Tap retry to fetch the latest attempt.
                </p>
            </div>

            <div
                v-else
                class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700"
            >
                <div class="flex items-center justify-between">
                    <span>Score</span>
                    <span class="text-lg font-semibold text-slate-900">{{
                        result?.score ?? 0
                    }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span>Submitted</span>
                    <span class="text-right text-slate-800">{{
                        result?.submitted_at || 'Just now'
                    }}</span>
                </div>
            </div>

            <div
                v-if="!loading && !error && result && result.wrong_questions.length > 0"
                class="space-y-3"
            >
                <h3 class="text-lg font-semibold text-slate-900">错题解释</h3>
                <article
                    v-for="wrong in result.wrong_questions"
                    :key="wrong.question_id"
                    class="rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900"
                >
                    <p class="font-medium">{{ wrong.content }}</p>
                    <p class="mt-2">正确答案：{{ wrong.correct_answer || '-' }}</p>
                    <p class="mt-1">解析：{{ wrong.explanation || '暂无解析' }}</p>
                    <div
                        v-if="Object.keys(wrong.option_explanations || {}).length > 0"
                        class="mt-2 space-y-1"
                    >
                        <p class="font-medium">选项解释：</p>
                        <p
                            v-for="(value, key) in wrong.option_explanations"
                            :key="`${wrong.question_id}-${key}`"
                        >
                            {{ key }}: {{ value }}
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-sm font-medium text-slate-700"
                    @click="loadResult"
                >
                    Retry
                </button>
                <Link
                    :href="quizRoutes.leaderboard().url"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    View leaderboard
                </Link>
            </div>
        </div>
    </div>
</template>
