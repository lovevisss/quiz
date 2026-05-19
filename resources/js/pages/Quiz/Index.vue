<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';

const steps = [
    { label: '首页', to: '/quiz' },
    { label: '答题', to: '/quiz/question' },
    { label: '成绩', to: '/quiz/result' },
    { label: '排行', to: '/quiz/leaderboard' },
    { label: '成就', to: '/quiz/certificate' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div
            class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-4 pt-4 pb-4 sm:px-5"
        >
            <header
                class="mb-4 rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-white via-sky-50 to-cyan-50 p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs tracking-[0.24em] text-sky-700 uppercase">
                            校园在线答题
                        </p>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">
                            移动端优先的答题流程
                        </h1>
                    </div>
                    <div class="rounded-2xl border border-white/80 bg-white/80 px-3 py-2 text-right shadow-sm">
                        <p class="text-[11px] text-slate-500">流程覆盖</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">首页 / 答题 / 成绩 / 排行 / 成就</p>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-600 sm:max-w-2xl">
                    从活动入口开始，完成答题后查看成绩、排行榜与成就中心，整体体验与手机端操作区保持一致。
                </p>
            </header>

            <div class="mb-3 flex gap-2 overflow-x-auto pb-1 text-xs">
                <RouterLink
                    v-for="step in steps"
                    :key="step.to"
                    :to="step.to"
                    class="rounded-full border border-slate-300 bg-white px-3 py-2 whitespace-nowrap text-slate-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-50"
                >
                    {{ step.label }}
                </RouterLink>
            </div>

            <main
                class="min-h-0 flex-1 overflow-y-auto rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
            >
                <RouterView v-slot="{ Component }">
                    <component :is="Component" />
                </RouterView>
            </main>

            <footer
                class="mt-4 border-t border-slate-200 bg-slate-100 px-4 py-3"
            >
                <div
                    class="mx-auto flex w-full flex-wrap justify-between gap-2 text-xs text-slate-600"
                >
                    <span>答题流程壳层</span>
                    <span>已适配安全区域与移动端底部操作区</span>
                </div>
            </footer>
        </div>
    </div>
</template>
