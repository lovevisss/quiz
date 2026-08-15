<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
    Award,
    CheckCircle2,
    ClipboardCopy,
    RefreshCw,
    Share2,
    XCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

type Achievement = {
    key: string;
    title: string;
    description: string;
    progress: string;
    unlocked: boolean;
    badge_label: string;
};

type WrongQuestion = {
    question_id: number;
    content: string | null;
    selected_answer_display: string;
    correct_answer_display: string;
    explanation: string | null;
};

type ResultState = {
    activity_id: number | null;
    score: number;
    submitted_at: string | null;
    total_questions: number;
    correct_count: number;
    wrong_count: number;
    wrong_questions: WrongQuestion[];
    newly_unlocked_achievements: Achievement[];
    share: {
        title: string;
        description: string;
        link: string;
        image: string;
    } | null;
};

const loading = ref(true);
const error = ref('');
const result = ref<ResultState | null>(null);
const sharing = ref(false);
const shareMessage = ref('');

const accuracy = computed(() => {
    if (!result.value?.total_questions) {
        return 0;
    }

    return Math.round(
        (result.value.correct_count / result.value.total_questions) * 100,
    );
});

function resolveAttemptId(): number | null {
    const raw = Number(
        new URLSearchParams(window.location.search).get('attempt'),
    );

    return Number.isInteger(raw) && raw > 0 ? raw : null;
}

function formatSubmittedAt(value: string | null): string {
    if (!value) {
        return '刚刚提交';
    }

    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleString('zh-CN');
}

function leaderboardHref(): string {
    if (!result.value?.activity_id) {
        return quizRoutes.leaderboard().url;
    }

    return `${quizRoutes.leaderboard().url}?activity=${result.value.activity_id}`;
}

async function loadResult(): Promise<void> {
    loading.value = true;
    error.value = '';
    shareMessage.value = '';

    const attemptId = resolveAttemptId();
    if (!attemptId) {
        result.value = null;
        error.value = '缺少答题记录，请完成答题后再查看结果。';
        loading.value = false;
        return;
    }

    try {
        const { data } = await axios.get(
            `/api/quiz/attempts/${attemptId}/result`,
        );
        result.value = {
            activity_id: data.activity_id ? Number(data.activity_id) : null,
            score: Number(data.score ?? 0),
            submitted_at: data.submitted_at ?? null,
            total_questions: Number(data.total_questions ?? 0),
            correct_count: Number(data.correct_count ?? 0),
            wrong_count: Number(data.wrong_count ?? 0),
            wrong_questions: Array.isArray(data.wrong_questions)
                ? data.wrong_questions
                : [],
            newly_unlocked_achievements: Array.isArray(
                data.newly_unlocked_achievements,
            )
                ? data.newly_unlocked_achievements
                : [],
            share:
                data.share && typeof data.share === 'object'
                    ? {
                          title: String(data.share.title ?? ''),
                          description: String(data.share.description ?? ''),
                          link: String(data.share.link ?? ''),
                          image: String(data.share.image ?? ''),
                      }
                    : null,
        };
    } catch {
        result.value = null;
        error.value = '成绩结果加载失败，请稍后重试。';
    } finally {
        loading.value = false;
    }
}

async function copyShareLink(): Promise<void> {
    shareMessage.value = '';
    const link = result.value?.share?.link;
    if (!link) {
        shareMessage.value = '当前成绩暂未生成分享链接。';
        return;
    }

    sharing.value = true;
    try {
        await navigator.clipboard?.writeText(link);
        shareMessage.value = '分享链接已复制，可发送到微信或朋友圈。';
    } catch {
        shareMessage.value = link;
    } finally {
        sharing.value = false;
    }
}

onMounted(loadResult);
</script>

<template>
    <main
        data-testid="quiz-result"
        class="mx-auto min-h-screen max-w-4xl px-4 pt-4 pb-32 sm:px-6"
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
                            答题完成
                        </p>
                        <h1 class="mt-3 text-2xl font-bold text-slate-950">
                            本次成绩
                        </h1>
                        <p class="mt-2 text-sm text-slate-500">
                            {{
                                formatSubmittedAt(result?.submitted_at ?? null)
                            }}
                        </p>
                    </div>
                    <div
                        class="rounded-3xl bg-emerald-600 px-5 py-4 text-center text-white"
                    >
                        <p class="text-xs opacity-80">得分</p>
                        <p class="mt-1 text-3xl font-bold">
                            {{ result?.score ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-3 gap-3">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-xs text-slate-500">正确率</p>
                        <p class="mt-2 text-xl font-bold text-slate-950">
                            {{ accuracy }}%
                        </p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50 p-4">
                        <p class="text-xs text-emerald-700">答对</p>
                        <p class="mt-2 text-xl font-bold text-emerald-700">
                            {{ result?.correct_count ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-2xl bg-amber-50 p-4">
                        <p class="text-xs text-amber-700">错题</p>
                        <p class="mt-2 text-xl font-bold text-amber-700">
                            {{ result?.wrong_count ?? 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="loading"
                class="rounded-3xl border border-slate-200 bg-white p-5 text-sm text-slate-600"
            >
                正在加载成绩...
            </div>

            <div
                v-else-if="error"
                class="rounded-3xl border border-rose-200 bg-rose-50 p-5 text-sm leading-6 text-rose-700"
            >
                {{ error }}
            </div>

            <template v-else-if="result">
                <section
                    v-if="result.newly_unlocked_achievements.length > 0"
                    class="rounded-3xl border border-amber-200 bg-amber-50 p-5"
                >
                    <div class="flex items-center gap-2">
                        <Award class="h-5 w-5 text-amber-700" />
                        <h2 class="text-lg font-bold text-slate-950">
                            新解锁成就
                        </h2>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <article
                            v-for="achievement in result.newly_unlocked_achievements"
                            :key="achievement.key"
                            class="rounded-2xl bg-white p-4 shadow-sm"
                        >
                            <p class="text-sm font-bold text-slate-950">
                                {{ achievement.title }}
                            </p>
                            <p class="mt-1 text-sm leading-6 text-slate-600">
                                {{ achievement.description }}
                            </p>
                            <p
                                class="mt-2 text-xs font-semibold text-amber-700"
                            >
                                {{ achievement.badge_label }} ·
                                {{ achievement.progress }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-bold text-slate-950">
                            错题解析
                        </h2>
                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600"
                        >
                            {{ result.wrong_questions.length }} 题
                        </span>
                    </div>

                    <div
                        v-if="result.wrong_questions.length === 0"
                        class="mt-4 flex items-start gap-3 rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800"
                    >
                        <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0" />
                        本次全部答对，没有需要复盘的错题。
                    </div>

                    <div v-else class="mt-4 space-y-3">
                        <article
                            v-for="wrong in result.wrong_questions"
                            :key="wrong.question_id"
                            class="rounded-2xl border border-slate-200 p-4"
                        >
                            <div class="flex items-start gap-2">
                                <XCircle
                                    class="mt-1 h-5 w-5 shrink-0 text-rose-600"
                                />
                                <p
                                    class="text-sm leading-6 font-semibold text-slate-950"
                                >
                                    {{ wrong.content }}
                                </p>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                <div class="rounded-2xl bg-rose-50 p-3">
                                    <p class="text-xs text-rose-600">
                                        你的答案
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-rose-700"
                                    >
                                        {{
                                            wrong.selected_answer_display ||
                                            '未作答'
                                        }}
                                    </p>
                                </div>
                                <div class="rounded-2xl bg-emerald-50 p-3">
                                    <p class="text-xs text-emerald-700">
                                        正确答案
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-semibold text-emerald-700"
                                    >
                                        {{
                                            wrong.correct_answer_display || '-'
                                        }}
                                    </p>
                                </div>
                            </div>
                            <p
                                class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm leading-6 text-slate-700"
                            >
                                {{ wrong.explanation || '暂无解析。' }}
                            </p>
                        </article>
                    </div>
                </section>

                <section
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <Share2 class="h-5 w-5 text-slate-600" />
                        <h2 class="text-lg font-bold text-slate-950">
                            分享成绩
                        </h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        复制公开分享链接后，可发送给好友或分享到微信。
                    </p>
                    <button
                        type="button"
                        class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white disabled:opacity-60"
                        :disabled="sharing"
                        @click="copyShareLink"
                    >
                        <ClipboardCopy class="h-4 w-4" />
                        {{ sharing ? '复制中...' : '复制分享链接' }}
                    </button>
                    <p
                        v-if="shareMessage"
                        class="mt-3 rounded-2xl bg-slate-50 p-3 text-sm break-all text-slate-700"
                    >
                        {{ shareMessage }}
                    </p>
                </section>
            </template>
        </section>

        <footer
            class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 px-4 py-3 backdrop-blur"
        >
            <div
                class="mx-auto grid max-w-4xl grid-cols-2 gap-3 pb-[env(safe-area-inset-bottom)] sm:grid-cols-3"
            >
                <button
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-2xl border border-slate-300 px-4 py-4 text-sm font-semibold text-slate-700"
                    @click="loadResult"
                >
                    <RefreshCw class="h-4 w-4" />
                    重新获取
                </button>
                <Link
                    :href="leaderboardHref()"
                    class="rounded-2xl bg-emerald-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    查看排行
                </Link>
                <Link
                    :href="quizRoutes.certificate().url"
                    class="col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-4 text-center text-sm font-semibold text-emerald-700 sm:col-span-1"
                >
                    查看证书
                </Link>
            </div>
        </footer>
    </main>
</template>
