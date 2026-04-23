<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

type QuizQuestion = {
    id: number;
    content: string;
    type: string;
    options: string[] | null;
};

type LabeledOption = {
    label: string;
    text: string;
};

const loading = ref(true);
const error = ref('');
const submitting = ref(false);
const questions = ref<QuizQuestion[]>([]);
const currentIndex = ref(0);
const selectedByQuestion = ref<Record<number, string>>({});
const activityId = ref<number | null>(null);
const attemptId = ref<number | null>(null);
const likedByQuestion = ref<Record<number, boolean>>({});
const correctionText = ref('');
const feedbackMessage = ref('');

const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null);
const totalQuestions = computed(() => questions.value.length);
const currentNumber = computed(() => Math.min(totalQuestions.value, currentIndex.value + 1));
const progressPercent = computed(() => {
    if (totalQuestions.value === 0) {
        return 0;
    }

    return Math.round((currentNumber.value / totalQuestions.value) * 100);
});
const canGoNext = computed(() => currentQuestion.value !== null && !submitting.value);

const optionList = computed(() => {
    if (!currentQuestion.value?.options || currentQuestion.value.options.length === 0) {
        return [];
    }

    return currentQuestion.value.options;
});

const labeledOptions = computed<LabeledOption[]>(() => {
    const labels = ['A', 'B', 'C', 'D', 'E', 'F'];

    return optionList.value.map((text, index) => ({
        label: labels[index] ?? String(index + 1),
        text,
    }));
});

async function resolveActivityId(): Promise<number | null> {
    const fromQuery = Number(new URLSearchParams(window.location.search).get('activity'));

    if (Number.isInteger(fromQuery) && fromQuery > 0) {
        return fromQuery;
    }

    try {
        const { data } = await axios.get('/api/quiz/activities/current');
        return data?.data?.id ? Number(data.data.id) : null;
    } catch {
        return null;
    }
}

async function loadQuestions(): Promise<void> {
    loading.value = true;
    error.value = '';

    activityId.value = await resolveActivityId();
    if (!activityId.value) {
        questions.value = [];
        error.value = 'No active activity available.';
        loading.value = false;

        return;
    }

    try {
        const questionsResponse = await axios.get(
            `/api/quiz/activities/${activityId.value}/questions`,
        );
        const data = questionsResponse.data;
        questions.value = Array.isArray(data?.data) ? data.data : [];
        currentIndex.value = 0;

        // Attempt creation can fail for unauthenticated users; questions should still be visible.
        try {
            const attemptResponse = await axios.post(
                `/api/quiz/activities/${activityId.value}/attempts`,
            );
            attemptId.value = Number(attemptResponse.data?.id ?? 0) || null;
        } catch {
            attemptId.value = null;
        }

        if (questions.value.length === 0) {
            error.value = 'This activity has no questions yet.';
        }
    } catch {
        questions.value = [];
        attemptId.value = null;
        error.value = 'Failed to load activity questions.';
    } finally {
        loading.value = false;
    }
}

function chooseOption(optionLabel: string): void {
    if (!currentQuestion.value) {
        return;
    }

    selectedByQuestion.value[currentQuestion.value.id] = optionLabel;
}

async function setLike(liked: boolean): Promise<void> {
    if (!currentQuestion.value) {
        return;
    }

    await axios.post(`/api/quiz/questions/${currentQuestion.value.id}/like`, { liked });
    likedByQuestion.value[currentQuestion.value.id] = liked;
}

async function submitCorrection(): Promise<void> {
    if (!currentQuestion.value || !correctionText.value.trim()) {
        return;
    }

    await axios.post(`/api/quiz/questions/${currentQuestion.value.id}/corrections`, {
        correction_text: correctionText.value.trim(),
    });

    feedbackMessage.value = '已提交纠错反馈，感谢你的帮助。';
    correctionText.value = '';
}

async function persistCurrentAnswer(): Promise<void> {
    if (!attemptId.value || !currentQuestion.value) {
        return;
    }

    const selected = selectedByQuestion.value[currentQuestion.value.id] ?? null;
    if (selected === null) {
        return;
    }

    await axios.put(
        `/api/quiz/attempts/${attemptId.value}/answers/${currentQuestion.value.id}`,
        {
            answer: {
                selected_option: selected,
            },
        },
    );
}

function resultHref(): string {
    if (!attemptId.value) {
        return quizRoutes.result().url;
    }

    return `${quizRoutes.result().url}?attempt=${attemptId.value}`;
}

async function goNext(): Promise<void> {
    if (!canGoNext.value) {
        return;
    }

    submitting.value = true;
    error.value = '';

    try {
        await persistCurrentAnswer();
        feedbackMessage.value = '';

        if (currentIndex.value < totalQuestions.value - 1) {
            currentIndex.value += 1;
            return;
        }

        if (!attemptId.value) {
            error.value = '未创建答题记录，请先登录后再完成提交。';
            return;
        }

        if (attemptId.value) {
            await axios.post(`/api/quiz/attempts/${attemptId.value}/submit`);
        }

        router.visit(resultHref());
    } catch {
        error.value = '提交失败，请稍后重试。';
    } finally {
        submitting.value = false;
    }
}

onMounted(loadQuestions);
</script>

<template>
    <div data-testid="quiz-question-card" class="flex min-h-[70vh] flex-col gap-6">
        <section class="space-y-4">
            <div class="flex items-center justify-between text-sm text-slate-600">
                <span>Question {{ currentNumber }} of {{ totalQuestions || 0 }}</span>
                <span>{{ progressPercent }}%</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full bg-sky-600"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>

            <article
                v-if="!loading && !error && currentQuestion"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
            >
                <p class="text-xs tracking-[0.2em] text-sky-700 uppercase">
                    Knowledge check
                </p>
                <h2 class="mt-2 text-xl leading-8 font-semibold text-slate-900">
                    {{ currentQuestion.content }}
                </h2>
            </article>

            <div
                v-if="loading"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                Loading activity questions...
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-700"
            >
                {{ error }}
            </div>

            <div v-else class="grid gap-3">
                <button
                    v-for="option in labeledOptions"
                    :key="`${currentQuestion?.id}-${option.label}`"
                    type="button"
                    @click="chooseOption(option.label)"
                    class="rounded-2xl border border-slate-300 bg-white px-4 py-4 text-left text-sm text-slate-800 transition hover:border-sky-500 hover:bg-sky-50"
                    :class="
                        currentQuestion && selectedByQuestion[currentQuestion.id] === option.label
                            ? 'border-sky-600 bg-sky-600 text-blue ring-2 ring-sky-300'
                            : ''
                    "
                >
                    {{ option.label }}. {{ option.text }}
                </button>
                <p v-if="optionList.length === 0" class="text-sm text-slate-600">
                    This question type does not have options. Continue to next.
                </p>

                <div
                    v-if="currentQuestion"
                    class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-3"
                >
                    <p class="text-sm font-medium text-slate-700">题目评价</p>
                    <div class="mt-2 flex gap-2">
                        <button
                            type="button"
                            class="rounded border px-3 py-1 text-sm"
                            :class="likedByQuestion[currentQuestion.id] ? 'border-rose-500 bg-rose-100 text-rose-700' : 'border-slate-300 text-slate-700'"
                            @click="setLike(true)"
                        >
                            喜欢
                        </button>
                        <button
                            type="button"
                            class="rounded border px-3 py-1 text-sm"
                            :class="likedByQuestion[currentQuestion.id] === false ? 'border-slate-600 bg-slate-200 text-slate-800' : 'border-slate-300 text-slate-700'"
                            @click="setLike(false)"
                        >
                            不喜欢
                        </button>
                    </div>

                    <div class="mt-3">
                        <label class="text-sm font-medium text-slate-700">纠错反馈</label>
                        <textarea
                            v-model="correctionText"
                            rows="2"
                            class="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-sm"
                            placeholder="题干/选项/答案有问题可在这里反馈"
                        ></textarea>
                        <button
                            type="button"
                            class="mt-2 rounded bg-emerald-600 px-3 py-1 text-sm text-white"
                            @click="submitCorrection"
                        >
                            提交纠错
                        </button>
                        <p v-if="feedbackMessage" class="mt-1 text-xs text-emerald-700">
                            {{ feedbackMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-2 gap-3">
                <Link
                    :href="quizRoutes.index().url"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-center text-sm font-medium text-slate-700"
                >
                    Previous
                </Link>
                <button
                    type="button"
                    data-testid="next-button"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canGoNext"
                    @click="goNext"
                >
                    {{ submitting ? 'Submitting...' : currentIndex < totalQuestions - 1 ? 'Next' : 'Finish' }}
                </button>
            </div>
        </div>
    </div>
</template>
