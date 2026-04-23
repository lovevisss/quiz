<script setup lang="ts">
import quizRoutes from '@/routes/quiz';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        data-testid="certificate-page"
        class="flex h-full flex-col justify-between gap-6"
    >
        <section class="space-y-4">
            <div
                class="rounded-[2rem] border border-amber-300/20 bg-gradient-to-br from-amber-200/20 via-white/5 to-sky-400/10 p-5 text-white shadow-2xl shadow-amber-950/20"
            >
                <p class="text-xs tracking-[0.2em] text-amber-100/80 uppercase">
                    Certificate ready
                </p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight">
                    Completion certificate
                </h2>
                <p class="mt-3 text-sm leading-6 text-slate-100/90">
                    Download and share the certificate after finishing the
                    challenge.
                </p>
            </div>
        </section>

        <div
            class="sticky bottom-0 -mx-4 border-t border-white/10 bg-slate-900/95 px-4 pt-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] sm:mx-0"
        >
            <div class="grid grid-cols-2 gap-3">
                <button
                    type="button"
                    class="rounded-2xl border border-white/10 px-4 py-4 text-sm font-medium text-slate-200"
                >
                    Download
                </button>
                <Link
                    :href="quizRoutes.index().url"
                    class="rounded-2xl bg-sky-400 px-4 py-4 text-center text-sm font-semibold text-slate-950"
                >
                    Back to home
                </Link>
            </div>
        </div>
    </div>
</template>
