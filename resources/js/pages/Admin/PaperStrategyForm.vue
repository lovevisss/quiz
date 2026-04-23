<template>
    <div class="mx-auto max-w-xl p-6">
        <h1 class="mb-4 text-2xl font-bold">
            {{ strategy ? '编辑策略' : '新建策略' }}
        </h1>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block">名称</label>
                <input
                    v-model="form.name"
                    required
                    class="w-full rounded border px-2 py-1"
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.name }}
                </p>
            </div>
            <div>
                <label class="block">模式</label>
                <select
                    v-model="form.mode"
                    required
                    class="w-full rounded border px-2 py-1"
                >
                    <option value="fixed">固定</option>
                    <option value="random">随机</option>
                </select>
                <p v-if="form.errors.mode" class="mt-1 text-sm text-red-600">
                    {{ form.errors.mode }}
                </p>
            </div>
            <div>
                <label class="block">配置（JSON格式）</label>
                <textarea
                    v-model="form.config"
                    class="w-full rounded border px-2 py-1"
                    rows="3"
                ></textarea>
                <p v-if="jsonError" class="mt-1 text-sm text-red-600">
                    {{ jsonError }}
                </p>
            </div>
            <div>
                <label class="block">状态</label>
                <select
                    v-model="form.status"
                    class="w-full rounded border px-2 py-1"
                >
                    <option :value="true">启用</option>
                    <option :value="false">禁用</option>
                </select>
            </div>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                {{ form.processing ? '保存中...' : '保存' }}
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
import adminPaperStrategies from '@/routes/admin/paper_strategies';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage();
const strategy = (page.props.strategy as { id: number; name: string; mode: string; config: unknown; status: boolean } | undefined) ?? null;
const jsonError = ref('');

const form = useForm({
    name: strategy ? strategy.name : '',
    mode: strategy ? strategy.mode : 'fixed',
    config: strategy ? JSON.stringify(strategy.config) : '',
    status: strategy ? !!strategy.status : true,
});

function submit(): void {
    jsonError.value = '';

    let configObj = {};
    if (form.config) {
        try {
            configObj = JSON.parse(form.config);
        } catch {
            jsonError.value = '配置必须为合法 JSON';
            return;
        }
    }

    form.transform(() => ({
        name: form.name,
        mode: form.mode,
        config: configObj,
        status: form.status,
    }));

    if (strategy) {
        form.put(adminPaperStrategies.update.url({ paper_strategy: strategy.id }));
    } else {
        form.post(adminPaperStrategies.store.url());
    }
}
</script>
