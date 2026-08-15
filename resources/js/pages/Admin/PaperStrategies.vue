<script setup lang="ts">
import adminPaperStrategies from '@/routes/admin/paper_strategies';
import { Link, router, usePage } from '@inertiajs/vue3';

type Strategy = {
    id: number;
    name: string;
    mode: 'fixed' | 'random';
    config: Record<string, unknown> | null;
    status: boolean;
};

const page = usePage();
const strategies = page.props.strategies as {
    data: Strategy[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

function remove(strategyId: number): void {
    if (confirm(`确定删除策略 #${strategyId} 吗？`)) {
        router.delete(
            adminPaperStrategies.destroy.url({ paper_strategy: strategyId }),
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }
}

function modeLabel(mode: string): string {
    return mode === 'fixed' ? '固定题组' : '随机组卷';
}

function configSummary(strategy: Strategy): string {
    const config = strategy.config ?? {};
    if (strategy.mode === 'fixed') {
        const questionIds = Array.isArray(config.question_ids)
            ? config.question_ids
            : [];
        return `${questionIds.length} 道固定题`;
    }

    const count = Number(config.count ?? 10);
    const ratios =
        config.tag_ratios && typeof config.tag_ratios === 'object'
            ? Object.keys(config.tag_ratios as Record<string, unknown>).length
            : 0;

    return `${count} 道题，${ratios} 个标签比例`;
}

function cleanPaginationLabel(label: string): string {
    return label.replace('&laquo;', '上一页').replace('&raquo;', '下一页');
}
</script>

<template>
    <div class="space-y-5 p-4 sm:p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">
                        试卷策略管理
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        配置固定题组或按标签比例随机抽题。
                    </p>
                </div>
                <Link
                    :href="adminPaperStrategies.create().url"
                    class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white"
                >
                    新建策略
                </Link>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                v-if="strategies.data.length === 0"
                class="rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
            >
                暂无试卷策略。
            </div>
            <div v-else class="grid gap-3">
                <article
                    v-for="strategy in strategies.data"
                    :key="strategy.id"
                    class="rounded-2xl border border-slate-200 p-4"
                >
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-bold text-slate-950">
                                    #{{ strategy.id }} {{ strategy.name }}
                                </h2>
                                <span
                                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                    >{{ modeLabel(strategy.mode) }}</span
                                >
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        strategy.status
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                >
                                    {{ strategy.status ? '启用' : '停用' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">
                                {{ configSummary(strategy) }}
                            </p>
                            <pre
                                class="mt-3 max-h-32 overflow-auto rounded bg-slate-50 p-3 text-xs text-slate-600"
                                >{{
                                    JSON.stringify(
                                        strategy.config ?? {},
                                        null,
                                        2,
                                    )
                                }}</pre
                            >
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <Link
                                :href="
                                    adminPaperStrategies.edit({
                                        paper_strategy: strategy.id,
                                    }).url
                                "
                                class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                            >
                                编辑
                            </Link>
                            <button
                                type="button"
                                class="rounded border border-red-200 px-4 py-2 text-sm font-semibold text-red-600"
                                @click="remove(strategy.id)"
                            >
                                删除
                            </button>
                        </div>
                    </div>
                </article>
            </div>

            <nav
                v-if="strategies.links?.length"
                class="mt-4 flex flex-wrap gap-2"
            >
                <Link
                    v-for="link in strategies.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    class="rounded border px-3 py-2 text-sm"
                    :class="[
                        link.active
                            ? 'border-slate-900 bg-slate-900 text-white'
                            : 'border-slate-300 text-slate-700',
                        !link.url ? 'pointer-events-none opacity-50' : '',
                    ]"
                    preserve-scroll
                >
                    {{ cleanPaginationLabel(link.label) }}
                </Link>
            </nav>
        </section>
    </div>
</template>
