<script setup lang="ts">
import adminActivities from '@/routes/admin/activities';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type ActivityItem = {
    id: number;
    name: string;
    description: string | null;
    start_date: string | null;
    end_date: string | null;
    enabled: boolean;
    paper_strategy_id?: number | null;
    paper_strategy: null | {
        id: number;
        name: string;
        mode: string;
        status: boolean;
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
const currentActivityId = computed(
    () => Number(page.props.currentActivityId ?? 0) || null,
);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    enabled: true,
    paper_strategy_id: null as number | null,
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.enabled = true;
    form.paper_strategy_id = null;
}

function edit(activity: ActivityItem): void {
    editingId.value = activity.id;
    form.name = activity.name;
    form.description = activity.description || '';
    form.start_date = formatDate(activity.start_date);
    form.end_date = formatDate(activity.end_date);
    form.enabled = activity.enabled;
    form.paper_strategy_id =
        activity.paper_strategy?.id ?? activity.paper_strategy_id ?? null;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: resetForm,
    };

    if (editingId.value) {
        form.put(
            adminActivities.update.url({ activity: editingId.value }),
            options,
        );
        return;
    }

    form.post(adminActivities.store.url(), options);
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
        return '';
    }

    return value.slice(0, 10);
}

function strategyLabel(activity: ActivityItem): string {
    if (!activity.paper_strategy) {
        return '默认随机题组';
    }

    return `${activity.paper_strategy.name} (${activity.paper_strategy.mode === 'fixed' ? '固定' : '随机'})`;
}
</script>

<template>
    <div class="space-y-5 p-4 sm:p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">活动管理</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        创建、编辑、启停答题活动，并绑定组卷策略。
                    </p>
                </div>
                <span
                    class="rounded-full bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700"
                >
                    当前生效：{{
                        currentActivityId ? `#${currentActivityId}` : '无'
                    }}
                </span>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-slate-950">
                        {{ editingId ? `编辑活动 #${editingId}` : '新建活动' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        活动启用且处于时间范围内时，手机端会优先进入该活动。
                    </p>
                </div>
                <button
                    v-if="editingId"
                    type="button"
                    class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    @click="resetForm"
                >
                    取消编辑
                </button>
            </div>

            <form class="grid gap-4 md:grid-cols-2" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-700">
                    名称
                    <input
                        v-model="form.name"
                        required
                        class="mt-1 w-full rounded border px-3 py-2"
                    />
                    <span
                        v-if="form.errors.name"
                        class="mt-1 block text-sm text-red-600"
                        >{{ form.errors.name }}</span
                    >
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    描述
                    <input
                        v-model="form.description"
                        class="mt-1 w-full rounded border px-3 py-2"
                    />
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    开始日期
                    <input
                        v-model="form.start_date"
                        type="date"
                        required
                        class="mt-1 w-full rounded border px-3 py-2"
                    />
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    结束日期
                    <input
                        v-model="form.end_date"
                        type="date"
                        required
                        class="mt-1 w-full rounded border px-3 py-2"
                    />
                </label>
                <label
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    试卷策略
                    <select
                        v-model="form.paper_strategy_id"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option :value="null">默认随机题组</option>
                        <option
                            v-for="strategy in strategies"
                            :key="strategy.id"
                            :value="strategy.id"
                        >
                            {{ strategy.name }}（{{
                                strategy.mode === 'fixed' ? '固定' : '随机'
                            }}）
                        </option>
                    </select>
                </label>
                <label
                    class="flex items-center gap-2 text-sm font-medium text-slate-700 md:col-span-2"
                >
                    <input v-model="form.enabled" type="checkbox" />
                    {{ editingId ? '保存后保持启用' : '创建后立即启用' }}
                </label>
                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? '保存中...'
                                : editingId
                                  ? '保存活动'
                                  : '新建活动'
                        }}
                    </button>
                    <button
                        v-if="editingId"
                        type="button"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        @click="resetForm"
                    >
                        取消
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-slate-950">活动列表</h2>
                <span class="text-sm text-slate-500"
                    >{{ activities.length }} 个活动</span
                >
            </div>

            <div class="grid gap-3">
                <article
                    v-for="activity in activities"
                    :key="activity.id"
                    class="rounded-2xl border border-slate-200 p-4"
                >
                    <div
                        class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-slate-950">
                                    #{{ activity.id }} {{ activity.name }}
                                </h3>
                                <span
                                    v-if="activity.id === currentActivityId"
                                    class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                    >当前生效</span
                                >
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        activity.enabled
                                            ? 'bg-sky-100 text-sky-700'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                >
                                    {{ activity.enabled ? '启用' : '停用' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                {{ activity.description || '暂无描述' }}
                            </p>
                            <div
                                class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2"
                            >
                                <p>
                                    时间：{{
                                        formatDate(activity.start_date) || '-'
                                    }}
                                    至
                                    {{ formatDate(activity.end_date) || '-' }}
                                </p>
                                <p>策略：{{ strategyLabel(activity) }}</p>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                                @click="edit(activity)"
                            >
                                编辑
                            </button>
                            <button
                                type="button"
                                class="rounded px-4 py-2 text-sm font-semibold"
                                :class="
                                    activity.enabled
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-emerald-600 text-white'
                                "
                                @click="toggleStatus(activity)"
                            >
                                {{ activity.enabled ? '停用' : '启用' }}
                            </button>
                        </div>
                    </div>
                </article>
            </div>
        </section>
    </div>
</template>
