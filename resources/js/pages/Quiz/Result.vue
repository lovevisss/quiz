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
    Share2,
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
    progress: string;
    unlocked: boolean;
    icon: string;
    tone: string;
    badge_label: string;
};

type ResultState = {
    activity_id: number | null;
    score: number;
    submitted_at: string | null;
    total_questions: number;
    correct_count: number;
    wrong_count: number;
    wrong_questions: Array<{
        question_id: number;
        content: string | null;
        your_answer: unknown;
        selected_answer_label: string | null;
        selected_answer_text: string | null;
        selected_answer_display: string;
        correct_answer: string | null;
        correct_answer_label: string | null;
        correct_answer_text: string | null;
        correct_answer_display: string;
        explanation: string | null;
    }>;
    newly_unlocked_achievements: Achievement[];
    share: {
        title: string;
        description: string;
        link: string;
        image: string;
    } | null;
};

type WeChatSdk = {
    config: (payload: Record<string, unknown>) => void;
    ready: (callback: () => void) => void;
    error: (callback: (error: unknown) => void) => void;
    updateTimelineShareData: (
        payload: Record<string, unknown>,
        callback?: (result?: unknown) => void,
    ) => void;
    updateAppMessageShareData: (
        payload: Record<string, unknown>,
        callback?: (result?: unknown) => void,
    ) => void;
};

const loading = ref(true);
const error = ref('');
const result = ref<ResultState | null>(null);
const shareMessage = ref('');
const shareError = ref('');
const sharing = ref(false);

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

const unlockedBadgeTitle = computed(() => {
    const count = result.value?.newly_unlocked_achievements.length ?? 0;

    if (count === 0) {
        return '';
    }

    return count === 1 ? '恭喜解锁新徽章！' : `恭喜解锁 ${count} 枚新徽章！`;
});

function badgeToneClass(tone: string): string {
    const palette: Record<string, string> = {
        sky: 'from-sky-500 to-cyan-500',
        indigo: 'from-indigo-500 to-violet-500',
        amber: 'from-amber-500 to-yellow-500',
        emerald: 'from-emerald-500 to-lime-500',
        yellow: 'from-yellow-500 to-orange-400',
        violet: 'from-violet-500 to-fuchsia-500',
        rose: 'from-rose-500 to-orange-500',
        orange: 'from-orange-500 to-amber-500',
        fuchsia: 'from-fuchsia-500 to-pink-500',
    };

    return palette[tone] ?? palette.sky;
}

function iconComponent(name: string) {
    return iconMap[name as keyof typeof iconMap] ?? Sparkles;
}

function resolveAttemptId(): number | null {
    const raw = Number(new URLSearchParams(window.location.search).get('attempt'));

    if (!Number.isInteger(raw) || raw <= 0) {
        return null;
    }

    return raw;
}

function formatSubmittedAt(value: string | null): string {
    if (!value) {
        return '刚刚提交';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString('zh-CN');
}

function leaderboardHref(): string {
    const params = new URLSearchParams();

    if (result.value?.activity_id) {
        params.set('activity', String(result.value.activity_id));
    }

    const query = params.toString();

    return query ? `${quizRoutes.leaderboard().url}?${query}` : quizRoutes.leaderboard().url;
}

async function loadResult() {
    loading.value = true;
    error.value = '';
    shareMessage.value = '';
    shareError.value = '';

    const attemptId = resolveAttemptId();
    if (!attemptId) {
        result.value = null;
        error.value = '缺少答题记录，请从答题页正常完成后再查看结果。';
        loading.value = false;

        return;
    }

    try {
        const { data } = await axios.get(`/api/quiz/attempts/${attemptId}/result`);
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
            newly_unlocked_achievements: Array.isArray(data.newly_unlocked_achievements)
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
        error.value = '当前无法加载成绩结果，请稍后再试。';
    } finally {
        loading.value = false;
    }
}

function currentPageUrl(): string {
    return window.location.href.split('#')[0] ?? window.location.href;
}

function isWeChatBrowser(): boolean {
    return /MicroMessenger/i.test(window.navigator.userAgent);
}

function getWeChatSdk(): WeChatSdk | null {
    return (window as Window & { wx?: WeChatSdk }).wx ?? null;
}

async function ensureWeChatSdk(): Promise<WeChatSdk> {
    const existingSdk = getWeChatSdk();

    if (existingSdk) {
        return existingSdk;
    }

    await new Promise<void>((resolve, reject) => {
        const existingScript = document.querySelector<HTMLScriptElement>('script[data-wechat-sdk="true"]');

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('微信 SDK 加载失败')), { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js';
        script.async = true;
        script.dataset.wechatSdk = 'true';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('微信 SDK 加载失败'));
        document.head.appendChild(script);
    });

    const sdk = getWeChatSdk();

    if (!sdk) {
        throw new Error('微信 SDK 未就绪');
    }

    return sdk;
}

function configureWeChatSdk(sdk: WeChatSdk, config: Record<string, unknown>): Promise<void> {
    return new Promise((resolve, reject) => {
        sdk.ready(() => resolve());
        sdk.error((sdkError) => reject(sdkError));
        sdk.config({
            ...config,
            debug: false,
        });
    });
}

function updateWeChatShareData(sdk: WeChatSdk, share: NonNullable<ResultState['share']>): Promise<void> {
    const timelinePayload = {
        title: share.title,
        link: share.link,
        imgUrl: share.image,
    };

    const messagePayload = {
        title: share.title,
        desc: share.description,
        link: share.link,
        imgUrl: share.image,
    };

    return new Promise((resolve) => {
        sdk.updateTimelineShareData(timelinePayload, () => undefined);
        sdk.updateAppMessageShareData(messagePayload, () => undefined);
        resolve();
    });
}

async function copyShareLink(link: string): Promise<boolean> {
    if (!link) {
        return false;
    }

    if (window.navigator.clipboard?.writeText) {
        await window.navigator.clipboard.writeText(link);

        return true;
    }

    const input = document.createElement('input');
    input.value = link;
    input.style.position = 'fixed';
    input.style.opacity = '0';
    document.body.appendChild(input);
    input.select();

    const copied = document.execCommand('copy');
    document.body.removeChild(input);

    return copied;
}

async function shareToTimeline(): Promise<void> {
    shareMessage.value = '';
    shareError.value = '';

    const share = result.value?.share;

    if (!share?.link) {
        shareError.value = '当前成绩尚未生成可分享链接，请先完成答题并刷新结果。';

        return;
    }

    sharing.value = true;

    try {
        if (isWeChatBrowser()) {
            const sdk = await ensureWeChatSdk();
            const { data } = await axios.post('/api/quiz/wechat/share-config', {
                url: currentPageUrl(),
            });

            if (data?.enabled && data?.config) {
                await configureWeChatSdk(sdk, data.config as Record<string, unknown>);
                await updateWeChatShareData(sdk, share);
                shareMessage.value = '朋友圈分享内容已准备好，请点击右上角“···”后选择“分享到朋友圈”。';

                return;
            }
        }

        const copied = await copyShareLink(share.link);
        shareMessage.value = copied
            ? '已复制分享链接。可在微信中打开后，再分享到朋友圈。'
            : '当前环境不支持自动复制，请手动复制分享链接。';
    } catch {
        shareError.value = '暂时无法完成微信分享配置，请稍后重试。';
    } finally {
        sharing.value = false;
    }
}

onMounted(loadResult);
</script>

<template>
    <div
        data-testid="quiz-result"
        class="flex h-full flex-col gap-5 pt-2"
    >
        <section class="space-y-4">
            <div
                class="rounded-[1.75rem] border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-sky-50 p-5 text-emerald-900 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs tracking-[0.2em] text-emerald-700 uppercase">答题完成</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">
                            本次成绩概览
                        </h2>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/80 px-3 py-2 text-right shadow-sm">
                        <p class="text-[11px] text-slate-500">提交时间</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">
                            {{ formatSubmittedAt(result?.submitted_at ?? null) }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-slate-700">
                        得分 {{ result?.score ?? 0 }}
                    </span>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700">
                        答对 {{ result?.correct_count ?? 0 }} 题
                    </span>
                    <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">
                        错题 {{ result?.wrong_count ?? 0 }} 题
                    </span>
                </div>
                <p class="mt-3 text-sm text-slate-600">
                    可查看得分、错题和解析，方便你快速复盘并继续提升。
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-full border border-emerald-300 bg-white/90 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-400 hover:bg-white"
                        :disabled="sharing"
                        @click="shareToTimeline"
                    >
                        <Share2 class="h-4 w-4" />
                        {{ sharing ? '准备分享中...' : '分享到微信朋友圈' }}
                    </button>
                </div>
                <p v-if="shareMessage" class="mt-3 rounded-2xl bg-emerald-100 px-4 py-3 text-sm text-emerald-700">
                    {{ shareMessage }}
                </p>
                <p v-if="shareError" class="mt-3 rounded-2xl bg-rose-100 px-4 py-3 text-sm text-rose-700">
                    {{ shareError }}
                </p>
            </div>

            <div
                v-if="loading"
                class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600"
            >
                正在加载成绩...
            </div>

            <div
                v-else-if="error"
                class="rounded-2xl border border-rose-300 bg-rose-50 p-4 text-sm text-rose-700"
            >
                <p class="font-medium">{{ error }}</p>
                <p class="mt-1 text-rose-700/80">点击下方“重新获取”可以再次尝试。</p>
            </div>

            <div
                v-else
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
            >
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">得分</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ result?.score ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">答对题数</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">
                        {{ result?.correct_count ?? 0 }}/{{ result?.total_questions ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">错题数量</p>
                    <p class="mt-2 text-3xl font-bold text-amber-600">
                        {{ result?.wrong_count ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs text-slate-500">完成状态</p>
                    <p class="mt-2 text-sm font-semibold text-slate-900">已提交并生成成绩</p>
                    <p class="mt-1 text-xs text-slate-500">可继续查看排行榜与成就</p>
                </div>
            </div>

            <div
                v-if="!loading && !error && result && result.newly_unlocked_achievements.length > 0"
                class="rounded-3xl border border-fuchsia-200 bg-gradient-to-br from-fuchsia-50 via-white to-amber-50 p-5 shadow-sm"
            >
                <p class="text-xs tracking-[0.2em] text-fuchsia-600 uppercase">徽章解锁</p>
                <h3 class="mt-2 text-xl font-semibold text-slate-900">{{ unlockedBadgeTitle }}</h3>
                <p class="mt-2 text-sm text-slate-600">本次答题触发的新成就会显示在这里，继续保持状态还能解锁更多徽章。</p>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="(achievement, index) in result.newly_unlocked_achievements"
                        :key="achievement.key"
                        class="result-badge-card rounded-3xl border border-white/70 bg-white/90 p-4 shadow-sm"
                        :style="{ animationDelay: `${index * 140}ms` }"
                    >
                        <div class="flex items-start gap-3">
                            <span :class="['flex h-12 w-12 items-center justify-center rounded-3xl bg-gradient-to-br text-white shadow-lg', badgeToneClass(achievement.tone)]">
                                <component :is="iconComponent(achievement.icon)" class="h-6 w-6" />
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="text-base font-semibold text-slate-900">{{ achievement.title }}</p>
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-500">
                                        {{ achievement.badge_label }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-slate-600">{{ achievement.description }}</p>
                                <p class="mt-2 text-xs font-medium text-fuchsia-600">进度：{{ achievement.progress }}</p>
                            </div>
                        </div>
                    </article>
                </div>
            </div>

            <div
                v-if="!loading && !error && result && result.wrong_questions.length === 0"
                class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"
            >
                恭喜你，本次答题全部正确，没有错题需要复盘。
            </div>

            <div
                v-if="!loading && !error && result && result.wrong_questions.length > 0"
                class="space-y-3"
            >
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900">错题解析</h3>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700">
                        共 {{ result.wrong_questions.length }} 题
                    </span>
                </div>
                <article
                    v-for="wrong in result.wrong_questions"
                    :key="wrong.question_id"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 shadow-sm"
                >
                    <p class="font-medium text-slate-900">{{ wrong.content }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-xl border border-rose-200 bg-white p-3">
                            <p class="text-xs text-rose-500">你的作答</p>
                            <p class="mt-1 font-medium text-rose-700">
                                {{ wrong.selected_answer_display || '未作答' }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-white p-3">
                            <p class="text-xs text-emerald-600">正确答案</p>
                            <p class="mt-1 font-medium text-emerald-700">
                                {{ wrong.correct_answer_display || '-' }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 rounded-xl bg-white/80 p-3 text-slate-700">
                        <span class="font-medium text-slate-900">解析：</span>
                        {{ wrong.explanation || '暂无解析' }}
                    </p>
                </article>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <button
                    type="button"
                    class="rounded-2xl border border-slate-300 px-4 py-4 text-sm font-medium text-slate-700"
                    @click="loadResult"
                >
                    重新获取
                </button>
                <button
                    type="button"
                    class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-4 text-sm font-semibold text-emerald-700 disabled:opacity-50"
                    :disabled="sharing"
                    @click="shareToTimeline"
                >
                    {{ sharing ? '准备分享中...' : '分享到朋友圈' }}
                </button>
                <Link
                    :href="leaderboardHref()"
                    class="rounded-2xl bg-sky-600 px-4 py-4 text-center text-sm font-semibold text-white"
                >
                    查看排行榜
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.result-badge-card {
    animation: unlock-pop 0.8s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes unlock-pop {
    0% {
        opacity: 0;
        transform: scale(0.72) translateY(18px) rotate(-4deg);
    }

    60% {
        opacity: 1;
        transform: scale(1.04) translateY(-3px) rotate(1deg);
    }

    100% {
        opacity: 1;
        transform: scale(1) translateY(0) rotate(0deg);
    }
}
</style>

