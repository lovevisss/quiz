<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import axios from 'axios';
import { Link, router } from '@inertiajs/vue3';
import { CheckCircle2 } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type QuizQuestion = {
    id: number;
    content: string;
    type: string;
    options: string[] | null;
    likes_count?: number;
    dislikes_count?: number;
};

type LabeledOption = {
    label: string;
    text: string;
};

type FeedbackStats = {
    likes: number;
    dislikes: number;
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
const feedbackStatsByQuestion = ref<Record<number, FeedbackStats>>({});
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
const currentSelectedOption = computed(() => {
    if (!currentQuestion.value) {
        return null;
    }

    return selectedByQuestion.value[currentQuestion.value.id] ?? null;
});
const isPreviewMode = computed(() => !attemptId.value);

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

const currentFeedbackStats = computed<FeedbackStats>(() => {
    if (!currentQuestion.value) {
        return { likes: 0, dislikes: 0 };
    }

    return feedbackStatsByQuestion.value[currentQuestion.value.id] ?? {
        likes: 0,
        dislikes: 0,
    };
});

function initializeFeedbackStats(items: QuizQuestion[]): void {
    feedbackStatsByQuestion.value = items.reduce<Record<number, FeedbackStats>>((carry, item) => {
        carry[item.id] = {
            likes: Number(item.likes_count ?? 0),
            dislikes: Number(item.dislikes_count ?? 0),
        };

        return carry;
    }, {});
}

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
        error.value = '当前没有可参与的活动。';
        loading.value = false;

        return;
    }

    try {
        const questionsResponse = await axios.get(
            `/api/quiz/activities/${activityId.value}/questions`,
        );
        const data = questionsResponse.data;
        questions.value = Array.isArray(data?.data) ? data.data : [];
        initializeFeedbackStats(questions.value);
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
            error.value = '当前活动暂时还没有题目。';
        }
    } catch {
        questions.value = [];
        attemptId.value = null;
        error.value = '题目加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

function chooseOption(optionLabel: string): void {
    if (!currentQuestion.value) {
        return;
    }

    selectedByQuestion.value[currentQuestion.value.id] = optionLabel;
    feedbackMessage.value = '';
}

function isSelectedOption(optionLabel: string): boolean {
    return currentSelectedOption.value === optionLabel;
}

async function setLike(liked: boolean): Promise<void> {
    if (!currentQuestion.value) {
        return;
    }

    try {
        const { data } = await axios.post(`/api/quiz/questions/${currentQuestion.value.id}/like`, { liked });
        likedByQuestion.value[currentQuestion.value.id] = liked;
        feedbackStatsByQuestion.value[currentQuestion.value.id] = {
            likes: Number(data?.likes_count ?? currentFeedbackStats.value.likes),
            dislikes: Number(data?.dislikes_count ?? currentFeedbackStats.value.dislikes),
        };
        feedbackMessage.value = liked ? '已记录为喜欢这道题。' : '已记录你的不喜欢评价。';
    } catch {
        feedbackMessage.value = '评价提交失败，请稍后重试。';
    }
}

async function submitCorrection(): Promise<void> {
    if (!currentQuestion.value || !correctionText.value.trim()) {
        return;
    }

    try {
        await axios.post(`/api/quiz/questions/${currentQuestion.value.id}/corrections`, {
            correction_text: correctionText.value.trim(),
        });

        feedbackMessage.value = '已提交纠错反馈，感谢你的帮助。';
        correctionText.value = '';
    } catch {
        feedbackMessage.value = '纠错提交失败，请稍后重试。';
    }
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
    const params = new URLSearchParams();

    if (attemptId.value) {
        params.set('attempt', String(attemptId.value));
    }

    if (activityId.value) {
        params.set('activity', String(activityId.value));
    }

    const query = params.toString();

    return query ? `${quizRoutes.result().url}?${query}` : quizRoutes.result().url;
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
    <div data-testid="quiz-question-card" class="flex min-h-[70vh] flex-col gap-5">
        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
                <span>第 {{ currentNumber }} / {{ totalQuestions || 0 }} 题</span>
                <div class="flex items-center gap-2">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">进度 {{ progressPercent }}%</span>
                    <span
                        v-if="activityId"
                        class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700"
                    >
                        活动 {{ activityId }}
                    </span>
                </div>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full bg-sky-600"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>

            <article
                v-if="!loading && !error && currentQuestion"
                class="rounded-[1.6rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-sky-50 p-4 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-sky-700 uppercase">
                            在线答题
                        </p>
                        <h2 class="mt-2 text-lg leading-8 font-semibold text-slate-900 sm:text-xl">
                            {{ currentQuestion.content }}
                        </h2>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm">
                        {{ currentQuestion.type === 'multiple' ? '多选题' : currentQuestion.type === 'text' ? '问答题' : '单选题' }}
                    </span>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs text-slate-500">
                    <span class="rounded-full bg-white/90 px-3 py-1">请点击选项完成作答</span>
                    <span v-if="isPreviewMode" class="rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                        当前为预览模式，登录后可保存成绩
                    </span>
                </div>
            </article>

            <div
                v-if="loading"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                正在加载题目...
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
                    class="rounded-2xl border bg-white px-4 py-4 text-left transition duration-200 hover:-translate-y-0.5 hover:border-sky-400 hover:bg-sky-50/60"
                    :class="
                        isSelectedOption(option.label)
                            ? 'border-sky-500 bg-sky-50 text-slate-900 shadow-[0_10px_30px_-18px_rgba(14,165,233,0.85)] ring-2 ring-sky-200'
                            : 'border-slate-300 text-slate-800'
                    "
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-2xl border text-sm font-semibold transition"
                            :class="
                                isSelectedOption(option.label)
                                    ? 'border-sky-600 bg-sky-600 text-white'
                                    : 'border-slate-300 bg-slate-50 text-slate-700'
                            "
                        >
                            {{ option.label }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-slate-900">选项 {{ option.label }}</span>
                                <span
                                    v-if="isSelectedOption(option.label)"
                                    class="inline-flex items-center gap-1 rounded-full bg-sky-600 px-2.5 py-1 text-xs font-medium text-white"
                                >
                                    <CheckCircle2 class="h-3.5 w-3.5" />
                                    已选择
                                </span>
                            </div>
                            <p class="mt-2 break-words text-sm leading-6 text-slate-700">
                                {{ option.text }}
                            </p>
                        </div>
                    </div>
                </button>
                <p v-if="optionList.length === 0" class="text-sm text-slate-600">
                    该题型没有可选项，请直接继续下一题。
                </p>

                <div
                    v-if="currentQuestion"
                    class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-slate-700">题目评价</p>
                            <p class="mt-1 text-xs text-slate-500">
                                当前统计：{{ currentFeedbackStats.likes }} 人喜欢，{{ currentFeedbackStats.dislikes }} 人不喜欢
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-xl border px-3 py-2 text-sm font-medium transition"
                                :class="likedByQuestion[currentQuestion.id] ? 'border-rose-500 bg-rose-100 text-rose-700' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'"
                                @click="setLike(true)"
                            >
                                喜欢
                            </button>
                            <button
                                type="button"
                                class="rounded-xl border px-3 py-2 text-sm font-medium transition"
                                :class="likedByQuestion[currentQuestion.id] === false ? 'border-slate-600 bg-slate-200 text-slate-800' : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-100'"
                                @click="setLike(false)"
                            >
                                不喜欢
                            </button>
                        </div>
                    </div>

                    <div class="mt-3 rounded-2xl bg-white p-3 shadow-sm">
                        <label class="text-sm font-medium text-slate-700">纠错反馈</label>
                        <textarea
                            v-model="correctionText"
                            rows="2"
                            class="mt-2 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:ring-2 focus:ring-sky-200"
                            placeholder="题干、选项或答案有问题时，可在这里反馈"
                        ></textarea>
                        <button
                            type="button"
                            class="mt-3 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                            @click="submitCorrection"
                        >
                            提交纠错
                        </button>
                        <p v-if="feedbackMessage" class="mt-2 text-xs text-emerald-700">
                            {{ feedbackMessage }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <Link
                    :href="quizRoutes.index().url"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-center text-sm font-medium text-slate-700"
                >
                    返回首页
                </Link>
                <button
                    type="button"
                    data-testid="next-button"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="!canGoNext"
                    @click="goNext"
                >
                    {{ submitting ? '提交中...' : currentIndex < totalQuestions - 1 ? '下一题' : '完成答题' }}
                </button>
            </div>
        </div>
    </div>
</template>
