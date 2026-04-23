<template>
    <div class="space-y-4 p-6">
        <div class="flex items-center justify-between gap-3">
            <h1 class="text-2xl font-bold">试卷策略管理</h1>
            <Link
                :href="adminPaperStrategies.create().url"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                新建策略
            </Link>
        </div>

        <div class="overflow-x-auto rounded-lg border bg-white">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="border px-3 py-2 text-left">ID</th>
                        <th class="border px-3 py-2 text-left">名称</th>
                        <th class="border px-3 py-2 text-left">模式</th>
                        <th class="border px-3 py-2 text-left">配置</th>
                        <th class="border px-3 py-2 text-left">状态</th>
                        <th class="border px-3 py-2 text-left">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="strategy in strategies.data" :key="strategy.id">
                        <td class="border px-3 py-2">{{ strategy.id }}</td>
                        <td class="border px-3 py-2">{{ strategy.name }}</td>
                        <td class="border px-3 py-2">{{ strategy.mode }}</td>
                        <td class="border px-3 py-2">
                            <code class="text-xs">{{ JSON.stringify(strategy.config ?? {}) }}</code>
                        </td>
                        <td class="border px-3 py-2">
                            <span
                                class="rounded px-2 py-1 text-xs"
                                :class="strategy.status ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                            >
                                {{ strategy.status ? '启用' : '禁用' }}
                            </span>
                        </td>
                        <td class="border px-3 py-2">
                            <Link
                                :href="adminPaperStrategies.edit({ paper_strategy: strategy.id }).url"
                                class="mr-3 text-blue-600 underline"
                            >
                                编辑
                            </Link>
                            <button
                                type="button"
                                class="text-red-600 underline"
                                @click="remove(strategy.id)"
                            >
                                删除
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import adminPaperStrategies from '@/routes/admin/paper_strategies';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const strategies = page.props.strategies as {
    data: Array<{ id: number; name: string; mode: string; config: unknown; status: boolean }>;
};

function remove(strategyId: number): void {
    if (confirm('确定要删除该策略吗？')) {
        router.delete(adminPaperStrategies.destroy.url({ paper_strategy: strategyId }), {
            preserveScroll: true,
            preserveState: true,
        });
    }
}
</script>
