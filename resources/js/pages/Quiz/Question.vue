<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Check,
    ChevronLeft,
    Flag,
    MessageSquareWarning,
    ThumbsDown,
    ThumbsUp,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

type QuizQuestion = {
    id: number;
    content: string;
    type: 'single' | 'multiple' | 'text';
    options: string[] | Record<string, string> | null;
    tags?: string[] | null;
    likes_count?: number;
    dislikes_count?: number;
    feedback_count?: number;
};

type FeedbackStats = {
    likes: number;
    dislikes: number;
};

const labels = ['A', 'B', 'C', 'D', 'E', 'F'];
const loading = ref(true);
const error = ref('');
const notice = ref('');
const submitting = ref(false);
const questions = ref<QuizQuestion[]>([]);
const currentIndex = ref(0);
const selectedByQuestion = ref<Record<number, string | string[]>>({});
const textByQuestion = ref<Record<number, string>>({});
const activityId = ref<number | null>(null);
const activityName = ref('');
const attemptId = ref<number | null>(null);
const expiresAt = ref<string | null>(null);
const nowTick = ref(Date.now());
const likedByQuestion = ref<Record<number, boolean>>({});
const feedbackStatsByQuestion = ref<Record<number, FeedbackStats>>({});
const feedbackOpen = ref(false);
const correctionText = ref('');
const feedbackMessage = ref('');
let timerId: number | undefined;

const currentQuestion = computed(
    () => questions.value[currentIndex.value] ?? null,
);
const totalQuestions = computed(() => questions.value.length);
const currentNumber = computed(() =>
    Math.min(totalQuestions.value, currentIndex.value + 1),
);
const progressPercent = computed(() =>
    totalQuestions.value === 0
        ? 0
        : Math.round((currentNumber.value / totalQuestions.value) * 100),
);
const isPreviewMode = computed(() => !attemptId.value);
const remainingSeconds = computed(() => {
    if (!expiresAt.value) {
        return null;
    }

    return Math.max(
        0,
        Math.floor(
            (new Date(expiresAt.value).getTime() - nowTick.value) / 1000,
        ),
    );
});
const isExpired = computed(
    () => remainingSeconds.value !== null && remainingSeconds.value <= 0,
);
const canGoNext = computed(
    () =>
        currentQuestion.value !== null && !submitting.value && !isExpired.value,
);
const isLastQuestion = computed(
    () => currentIndex.value >= totalQuestions.value - 1,
);

const optionList = computed(() => {
    const options = currentQuestion.value?.options;
    if (!options) {
        return [];
    }

    return Array.isArray(options) ? options : Object.values(options);
});

const selectedCount = computed(
    () =>
        Object.keys({
            ...selectedByQuestion.value,
            ...textByQuestion.value,
        }).filter((key) => {
            const questionId = Number(key);
            const question = questions.value.find(
                (item) => item.id === questionId,
            );
            if (question?.type === 'text') {
                return Boolean(textByQuestion.value[questionId]?.trim());
            }

            const value = selectedByQuestion.value[questionId];
            return Array.isArray(value) ? value.length > 0 : Boolean(value);
        }).length,
);

const currentFeedbackStats = computed<FeedbackStats>(() => {
    if (!currentQuestion.value) {
        return { likes: 0, dislikes: 0 };
    }

    return (
        feedbackStatsByQuestion.value[currentQuestion.value.id] ?? {
            likes: 0,
            dislikes: 0,
        }
    );
});

function formatRemaining(seconds: number | null): string {
    if (seconds === null) {
        return '30:00';
    }

    const minutes = Math.floor(seconds / 60);
    const remain = seconds % 60;

    return `${String(minutes).padStart(2, '0')}:${String(remain).padStart(2, '0')}`;
}

function initializeFeedbackStats(items: QuizQuestion[]): void {
    feedbackStatsByQuestion.value = items.reduce<Record<number, FeedbackStats>>(
        (carry, item) => {
            carry[item.id] = {
                likes: Number(item.likes_count ?? 0),
                dislikes: Number(item.dislikes_count ?? 0),
            };

            return carry;
        },
        {},
    );
}

async function resolveActivityId(): Promise<number | null> {
    const fromQuery = Number(
        new URLSearchParams(window.location.search).get('activity'),
    );
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
    notice.value = '';

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
        activityName.value = data?.activity?.name ?? '';
        initializeFeedbackStats(questions.value);
        currentIndex.value = 0;

        try {
            const attemptResponse = await axios.post(
                `/api/quiz/activities/${activityId.value}/attempts`,
            );
            attemptId.value = Number(attemptResponse.data?.id ?? 0) || null;
            expiresAt.value = attemptResponse.data?.expires_at ?? null;
        } catch {
            attemptId.value = null;
            expiresAt.value = null;
            notice.value =
                '当前为预览模式。登录后开始答题可保存成绩和参与排行榜。';
        }

        if (questions.value.length === 0) {
            error.value = '当前活动暂时没有题目。';
        }
    } catch {
        questions.value = [];
        attemptId.value = null;
        error.value = '题目加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

function isSelected(optionLabel: string): boolean {
    if (!currentQuestion.value) {
        return false;
    }

    const value = selectedByQuestion.value[currentQuestion.value.id];
    return Array.isArray(value)
        ? value.includes(optionLabel)
        : value === optionLabel;
}

function chooseOption(optionLabel: string): void {
    if (!currentQuestion.value) {
        return;
    }

    feedbackMessage.value = '';
    notice.value = '';

    if (currentQuestion.value.type === 'multiple') {
        const current = selectedByQuestion.value[currentQuestion.value.id];
        const values = Array.isArray(current) ? [...current] : [];
        selectedByQuestion.value[currentQuestion.value.id] = values.includes(
            optionLabel,
        )
            ? values.filter((item) => item !== optionLabel)
            : [...values, optionLabel].sort();
        return;
    }

    selectedByQuestion.value[currentQuestion.value.id] = optionLabel;
}

function currentAnswerPayload(): Record<string, unknown> | null {
    const question = currentQuestion.value;
    if (!question) {
        return null;
    }

    if (question.type === 'text') {
        const value = textByQuestion.value[question.id]?.trim() ?? '';
        return value ? { value } : null;
    }

    const selected = selectedByQuestion.value[question.id];
    if (Array.isArray(selected)) {
        return selected.length > 0 ? { selected_option: selected } : null;
    }

    return selected ? { selected_option: selected } : null;
}

async function persistCurrentAnswer(): Promise<boolean> {
    if (!attemptId.value || !currentQuestion.value) {
        return true;
    }

    const answer = currentAnswerPayload();
    if (!answer) {
        notice.value = '请先完成当前题目再继续。';
        return false;
    }

    await axios.put(
        `/api/quiz/attempts/${attemptId.value}/answers/${currentQuestion.value.id}`,
        { answer },
    );
    return true;
}

function resultHref(): string {
    const params = new URLSearchParams();
    if (attemptId.value) {
        params.set('attempt', String(attemptId.value));
    }
    if (activityId.value) {
        params.set('activity', String(activityId.value));
    }

    return `${quizRoutes.result().url}?${params.toString()}`;
}

async function goNext(): Promise<void> {
    if (!canGoNext.value) {
        return;
    }

    submitting.value = true;
    error.value = '';

    try {
        const persisted = await persistCurrentAnswer();
        if (!persisted) {
            return;
        }

        feedbackMessage.value = '';
        feedbackOpen.value = false;
        correctionText.value = '';

        if (!isLastQuestion.value) {
            currentIndex.value += 1;
            return;
        }

        if (!attemptId.value) {
            error.value = '当前为预览模式，登录后才能提交成绩。';
            return;
        }

        await axios.post(`/api/quiz/attempts/${attemptId.value}/submit`);
        router.visit(resultHref());
    } catch {
        error.value = '提交失败，请检查网络后重试。';
    } finally {
        submitting.value = false;
    }
}

async function setLike(liked: boolean): Promise<void> {
    if (!currentQuestion.value) {
        return;
    }

    try {
        const { data } = await axios.post(
            `/api/quiz/questions/${currentQuestion.value.id}/like`,
            { liked },
        );
        likedByQuestion.value[currentQuestion.value.id] = liked;
        feedbackStatsByQuestion.value[currentQuestion.value.id] = {
            likes: Number(
                data?.likes_count ?? currentFeedbackStats.value.likes,
            ),
            dislikes: Number(
                data?.dislikes_count ?? currentFeedbackStats.value.dislikes,
            ),
        };
        feedbackMessage.value = liked
            ? '已记录喜欢这道题。'
            : '已记录你的反馈。';
    } catch {
        feedbackMessage.value = '评价提交失败，请稍后重试。';
    }
}

async function submitCorrection(): Promise<void> {
    if (!currentQuestion.value || !correctionText.value.trim()) {
        return;
    }

    try {
        await axios.post(
            `/api/quiz/questions/${currentQuestion.value.id}/corrections`,
            {
                correction_text: correctionText.value.trim(),
            },
        );
        correctionText.value = '';
        feedbackMessage.value = '纠错反馈已提交，感谢你的帮助。';
    } catch {
        feedbackMessage.value = '纠错提交失败，请稍后重试。';
    }
}

onMounted(() => {
    void loadQuestions();
    timerId = window.setInterval(() => {
        nowTick.value = Date.now();
    }, 1000);
});

onUnmounted(() => {
    if (timerId) {
        window.clearInterval(timerId);
    }
});
</script>

<template>
    <main
        data-testid="quiz-question-card"
        class="mx-auto min-h-screen max-w-3xl bg-slate-50 px-4 pt-3 pb-32 sm:bg-transparent sm:px-6"
    >
        <header
            class="sticky top-0 z-10 -mx-4 border-b border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-b-3xl sm:border sm:shadow-sm"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-slate-500">
                        {{ activityName || '在线答题' }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-950">
                        第 {{ currentNumber }} / {{ totalQuestions || 0 }} 题
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <span
                        v-if="isPreviewMode"
                        class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"
                        >预览</span
                    >
                    <span
                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                    >
                        {{ formatRemaining(remainingSeconds) }}
                    </span>
                </div>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-200">
                <div
                    class="h-full rounded-full bg-emerald-600 transition-all"
                    :style="{ width: `${progressPercent}%` }"
                ></div>
            </div>
        </header>

        <section class="mt-4 space-y-4">
            <div
                v-if="loading"
                class="rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600"
            >
                正在加载题目...
            </div>

            <div
                v-else-if="error"
                class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm leading-6 text-rose-700"
            >
                {{ error }}
            </div>

            <template v-else-if="currentQuestion">
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700"
                        >
                            {{
                                currentQuestion.type === 'multiple'
                                    ? '多选题'
                                    : currentQuestion.type === 'text'
                                      ? '问答题'
                                      : '单选题'
                            }}
                        </span>
                        <span
                            v-for="tag in currentQuestion.tags ?? []"
                            :key="tag"
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-600"
                        >
                            {{ tag }}
                        </span>
                    </div>
                    <h1
                        class="mt-4 text-lg leading-8 font-bold text-slate-950 sm:text-xl"
                    >
                        {{ currentQuestion.content }}
                    </h1>
                    <p class="mt-3 text-sm text-slate-500">
                        {{
                            currentQuestion.type === 'multiple'
                                ? '可选择多个答案。'
                                : currentQuestion.type === 'text'
                                  ? '请输入你的答案。'
                                  : '请选择一个答案。'
                        }}
                    </p>
                </article>

                <div
                    v-if="notice"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                >
                    {{ notice }}
                </div>

                <div
                    v-if="isExpired"
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                >
                    本次答题已超时，请返回首页重新开始。
                </div>

                <div
                    v-if="currentQuestion.type === 'text'"
                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <label
                        class="text-sm font-semibold text-slate-900"
                        for="text-answer"
                        >你的答案</label
                    >
                    <textarea
                        id="text-answer"
                        v-model="textByQuestion[currentQuestion.id]"
                        rows="6"
                        class="mt-3 w-full resize-none rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-base leading-7 text-slate-900 transition outline-none focus:border-emerald-500 focus:bg-white focus:ring-2 focus:ring-emerald-100"
                        placeholder="在这里输入答案"
                    ></textarea>
                </div>

                <div v-else class="grid gap-3">
                    <button
                        v-for="(optionText, index) in optionList"
                        :key="`${currentQuestion.id}-${labels[index] ?? index}`"
                        type="button"
                        class="w-full rounded-3xl border bg-white p-4 text-left shadow-sm transition active:scale-[0.99]"
                        :class="
                            isSelected(labels[index] ?? String(index + 1))
                                ? 'border-emerald-500 ring-2 ring-emerald-100'
                                : 'border-slate-200'
                        "
                        @click="
                            chooseOption(labels[index] ?? String(index + 1))
                        "
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border text-sm font-bold"
                                :class="
                                    isSelected(
                                        labels[index] ?? String(index + 1),
                                    )
                                        ? 'border-emerald-600 bg-emerald-600 text-white'
                                        : 'border-slate-300 bg-slate-50 text-slate-700'
                                "
                            >
                                <Check
                                    v-if="
                                        isSelected(
                                            labels[index] ?? String(index + 1),
                                        )
                                    "
                                    class="h-5 w-5"
                                />
                                <span v-else>{{
                                    labels[index] ?? index + 1
                                }}</span>
                            </span>
                            <span
                                class="min-w-0 flex-1 text-base leading-7 text-slate-900"
                                >{{ optionText }}</span
                            >
                        </div>
                    </button>
                </div>

                <section
                    class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-3 text-left"
                        @click="feedbackOpen = !feedbackOpen"
                    >
                        <span
                            class="flex items-center gap-2 text-sm font-semibold text-slate-900"
                        >
                            <MessageSquareWarning
                                class="h-4 w-4 text-slate-500"
                            />
                            题目反馈
                        </span>
                        <span class="text-xs text-slate-500">
                            {{ currentFeedbackStats.likes }} 喜欢 ·
                            {{ currentFeedbackStats.dislikes }} 反馈
                        </span>
                    </button>
                    <div
                        v-if="feedbackOpen"
                        class="mt-4 space-y-3 border-t border-slate-100 pt-4"
                    >
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                class="flex items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold"
                                :class="
                                    likedByQuestion[currentQuestion.id]
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                        : 'border-slate-200 text-slate-700'
                                "
                                @click="setLike(true)"
                            >
                                <ThumbsUp class="h-4 w-4" />
                                喜欢
                            </button>
                            <button
                                type="button"
                                class="flex items-center justify-center gap-2 rounded-2xl border px-3 py-3 text-sm font-semibold"
                                :class="
                                    likedByQuestion[currentQuestion.id] ===
                                    false
                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                        : 'border-slate-200 text-slate-700'
                                "
                                @click="setLike(false)"
                            >
                                <ThumbsDown class="h-4 w-4" />
                                有问题
                            </button>
                        </div>
                        <textarea
                            v-model="correctionText"
                            rows="3"
                            class="w-full resize-none rounded-2xl border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                            placeholder="题干、选项或答案有问题时可在这里反馈"
                        ></textarea>
                        <button
                            type="button"
                            class="rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white disabled:opacity-50"
                            :disabled="!correctionText.trim()"
                            @click="submitCorrection"
                        >
                            提交纠错
                        </button>
                        <p
                            v-if="feedbackMessage"
                            class="text-sm text-emerald-700"
                        >
                            {{ feedbackMessage }}
                        </p>
                    </div>
                </section>
            </template>
        </section>

        <footer
            class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur"
        >
            <div
                class="mx-auto grid max-w-3xl grid-cols-[0.8fr_1.2fr] gap-3 pb-[env(safe-area-inset-bottom)]"
            >
                <Link
                    :href="quizRoutes.index().url"
                    class="flex items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-4 text-sm font-semibold text-slate-700"
                >
                    <ChevronLeft class="h-4 w-4" />
                    首页
                </Link>
                <button
                    type="button"
                    data-testid="next-button"
                    class="flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-4 py-4 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:bg-slate-300 disabled:text-slate-600"
                    :disabled="!canGoNext"
                    @click="goNext"
                >
                    <Flag v-if="isLastQuestion" class="h-4 w-4" />
                    {{
                        submitting
                            ? '处理中...'
                            : isLastQuestion
                              ? '提交答卷'
                              : '下一题'
                    }}
                    <span class="text-xs opacity-80"
                        >({{ selectedCount }}/{{ totalQuestions }})</span
                    >
                </button>
            </div>
        </footer>
    </main>
</template>
