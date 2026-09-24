<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Activity = {
    id: number;
    name: string;
    start_date: string | null;
    end_date: string | null;
};

type Row = {
    user_id: number;
    user_name: string;
    rank: number;
    score: number;
    duration_seconds: number | null;
    submitted_at: string;
};

defineProps<{ activity: Activity; rows: Row[] }>();

function formatDate(value: string | null): string {
    return value ? value.slice(0, 10) : '-';
}

function formatDuration(seconds: number | null): string {
    if (seconds === null) {
        return '-';
    }

    return `${Math.floor(seconds / 60)}分${String(seconds % 60).padStart(2, '0')}秒`;
}

function printList(): void {
    window.print();
}
</script>

<template>
    <Head :title="`${activity.name} · 排名清单`" />
    <main class="space-y-5 p-4 sm:p-6">
        <div
            class="flex flex-wrap items-start justify-between gap-3 print:hidden"
        >
            <Link
                href="/admin/activities"
                class="text-sm font-semibold text-emerald-700 hover:underline"
                >← 返回活动管理</Link
            >
            <button
                type="button"
                class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                @click="printList"
            >
                打印清单
            </button>
        </div>

        <section
            class="rounded-lg border bg-white p-4 shadow-sm print:border-0 print:p-0 print:shadow-none"
        >
            <p class="text-xs font-semibold tracking-wider text-emerald-700">
                活动排名清单
            </p>
            <h1 class="mt-2 text-2xl font-bold text-slate-950">
                {{ activity.name }}
            </h1>
            <p class="mt-2 text-sm text-slate-600">
                活动 #{{ activity.id }} ·
                {{ formatDate(activity.start_date) }} 至
                {{ formatDate(activity.end_date) }} · {{ rows.length }} 名参与者
            </p>
            <p class="mt-1 text-xs text-slate-500">
                每位参与者按最高分、最短用时和最早提交时间取最佳成绩。
            </p>
        </section>

        <section
            class="overflow-x-auto rounded-lg border bg-white shadow-sm print:border-0 print:shadow-none"
        >
            <p
                v-if="rows.length === 0"
                class="p-6 text-center text-sm text-slate-500"
            >
                此活动暂无已提交的成绩。
            </p>
            <table v-else class="w-full min-w-[620px] text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-600">
                    <tr>
                        <th scope="col" class="px-4 py-3">排名</th>
                        <th scope="col" class="px-4 py-3">参与者</th>
                        <th scope="col" class="px-4 py-3">用户 ID</th>
                        <th scope="col" class="px-4 py-3">得分</th>
                        <th scope="col" class="px-4 py-3">用时</th>
                        <th scope="col" class="px-4 py-3">提交时间</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="row in rows" :key="row.user_id">
                        <td class="px-4 py-3 font-bold text-slate-950">
                            {{ row.rank }}
                        </td>
                        <td class="px-4 py-3">{{ row.user_name }}</td>
                        <td class="px-4 py-3">{{ row.user_id }}</td>
                        <td class="px-4 py-3 font-semibold">{{ row.score }}</td>
                        <td class="px-4 py-3">
                            {{ formatDuration(row.duration_seconds) }}
                        </td>
                        <td class="px-4 py-3">{{ row.submitted_at }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
</template>
