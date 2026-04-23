<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router';

const steps = [
    { label: 'Home', to: '/quiz' },
    { label: 'Question', to: '/quiz/question' },
    { label: 'Result', to: '/quiz/result' },
    { label: 'Board', to: '/quiz/leaderboard' },
    { label: 'Cert', to: '/quiz/certificate' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <div
            class="mx-auto flex min-h-screen w-full max-w-md flex-col px-4 pt-4 pb-4"
        >
            <header
                class="mb-4 rounded-3xl border border-slate-200 bg-white p-4 shadow"
            >
                <p class="text-xs tracking-[0.3em] text-sky-700 uppercase">
                    Digital Campus Quiz
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">
                    Mobile-first activity flow
                </h1>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Start the activity, answer the question, review the result,
                    inspect the leaderboard, and download the certificate.
                </p>
            </header>

            <div class="mb-3 flex gap-2 overflow-x-auto pb-1 text-xs">
                <RouterLink
                    v-for="step in steps"
                    :key="step.to"
                    :to="step.to"
                    class="rounded-full border border-slate-300 bg-white px-3 py-2 whitespace-nowrap text-slate-700 transition hover:bg-slate-50"
                >
                    {{ step.label }}
                </RouterLink>
            </div>

            <main
                class="min-h-0 flex-1 overflow-y-auto rounded-[2rem] border border-slate-200 bg-white p-4 shadow"
            >
                <RouterView v-slot="{ Component }">
                    <component :is="Component" />
                </RouterView>
            </main>

            <footer
                class="mt-4 border-t border-slate-200 bg-slate-100 px-4 py-3"
            >
                <div
                    class="mx-auto flex w-full max-w-md justify-between gap-2 text-xs text-slate-600"
                >
                    <span>Quiz shell</span>
                    <span>Safe-area aware</span>
                </div>
            </footer>
        </div>
    </div>
</template>
