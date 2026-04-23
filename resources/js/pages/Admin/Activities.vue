<template>
    <div class="space-y-6 p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h1 class="mb-4 text-2xl font-bold">活动管理</h1>
            <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">名称</label>
                    <input
                        v-model="form.name"
                        required
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">描述</label>
                    <input
                        v-model="form.description"
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                        {{ form.errors.description }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">开始日期</label>
                    <input
                        v-model="form.start_date"
                        type="date"
                        required
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.start_date" class="mt-1 text-sm text-red-600">
                        {{ form.errors.start_date }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">结束日期</label>
                    <input
                        v-model="form.end_date"
                        type="date"
                        required
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.end_date" class="mt-1 text-sm text-red-600">
                        {{ form.errors.end_date }}
                    </p>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">试卷策略</label>
                    <select
                        v-model="form.paper_strategy_id"
                        class="w-full rounded border px-3 py-2"
                    >
                        <option :value="null">不绑定（默认规则）</option>
                        <option
                            v-for="strategy in strategies"
                            :key="strategy.id"
                            :value="strategy.id"
                        >
                            {{ strategy.name }} ({{ strategy.mode }})
                        </option>
                    </select>
                    <p v-if="form.errors.paper_strategy_id" class="mt-1 text-sm text-red-600">
                        {{ form.errors.paper_strategy_id }}
                    </p>
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-medium md:col-span-2">
                    <input v-model="form.enabled" type="checkbox" />
                    创建后立即启用
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                    >
                        {{ form.processing ? '提交中...' : '新建活动' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-xl font-semibold">活动列表</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border px-3 py-2 text-left">ID</th>
                            <th class="border px-3 py-2 text-left">名称</th>
                            <th class="border px-3 py-2 text-left">时间区间</th>
                            <th class="border px-3 py-2 text-left">策略</th>
                            <th class="border px-3 py-2 text-left">状态</th>
                            <th class="border px-3 py-2 text-left">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="activity in activities" :key="activity.id">
                            <td class="border px-3 py-2">{{ activity.id }}</td>
                            <td class="border px-3 py-2">
                                <p class="font-medium">{{ activity.name }}</p>
                                <p class="text-xs text-gray-500">{{ activity.description || '暂无描述' }}</p>
                            </td>
                            <td class="border px-3 py-2">
                                {{ formatDate(activity.start_date) }} - {{ formatDate(activity.end_date) }}
                            </td>
                            <td class="border px-3 py-2">
                                {{ activity.paper_strategy?.name || '默认' }}
                            </td>
                            <td class="border px-3 py-2">
                                <span
                                    class="rounded px-2 py-1 text-xs"
                                    :class="activity.enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'"
                                >
                                    {{ activity.enabled ? '启用' : '禁用' }}
                                </span>
                            </td>
                            <td class="border px-3 py-2">
                                <button
                                    type="button"
                                    @click="toggleStatus(activity)"
                                    class="text-blue-600 underline"
                                >
                                    {{ activity.enabled ? '禁用' : '启用' }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts">
import adminActivities from '@/routes/admin/activities';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type ActivityItem = {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    enabled: boolean;
    paper_strategy: null | {
        id: number;
        name: string;
        mode: string;
    };
};

type StrategyItem = {
    id: number;
    name: string;
    mode: string;
};

const page = usePage();
const activities = computed(
    () => (page.props.activities as ActivityItem[] | undefined) ?? [],
);
const strategies = computed(
    () => (page.props.strategies as StrategyItem[] | undefined) ?? [],
);

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    enabled: true,
    paper_strategy_id: null as number | null,
});

function submit(): void {
    form.post(adminActivities.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.enabled = true;
            form.paper_strategy_id = null;
        },
    });
}

function toggleStatus(activity: ActivityItem): void {
    router.patch(
        adminActivities.toggleStatus.url({ activity: activity.id }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function formatDate(value: string | null): string {
    if (!value) {
        return '-';
    }

    return value.slice(0, 10);
}
</script>
