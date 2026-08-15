<template>
    <div class="space-y-6 p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold">题目标签管理</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        在这里维护题库可选标签，`题库管理`
                        页面会直接从这里选择标签。
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/questions"
                        class="inline-flex rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                    >
                        返回管理题目
                    </Link>
                    <Link
                        href="/admin/questions/create"
                        class="inline-flex rounded border border-sky-200 bg-sky-50 px-4 py-2 text-sm font-medium text-sky-700"
                    >
                        去新增题目
                    </Link>
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-xl font-semibold">新增标签</h2>
            <form
                class="flex flex-col gap-3 sm:flex-row"
                @submit.prevent="submit"
            >
                <div class="flex-1">
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded border px-3 py-2"
                        placeholder="例如：网络安全 / 法律法规 / 基础常识"
                    />
                    <p
                        v-if="form.errors.name"
                        class="mt-1 text-sm text-red-600"
                    >
                        {{ form.errors.name }}
                    </p>
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                >
                    {{ form.processing ? '保存中...' : '添加标签' }}
                </button>
            </form>
            <p
                v-if="form.recentlySuccessful"
                class="mt-2 text-sm text-emerald-700"
            >
                标签已添加。
            </p>
            <p v-if="deleteError" class="mt-2 text-sm text-red-600">
                {{ deleteError }}
            </p>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">标签列表</h2>
                <span class="text-sm text-slate-500"
                    >共 {{ tags.length }} 个标签</span
                >
            </div>

            <div
                v-if="tags.length === 0"
                class="rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
            >
                还没有标签，先添加一个用于题目选择。
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="border px-3 py-2 text-left">ID</th>
                            <th class="border px-3 py-2 text-left">标签名称</th>
                            <th class="border px-3 py-2 text-left">
                                已使用题目数
                            </th>
                            <th class="border px-3 py-2 text-left">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="tag in tags" :key="tag.id">
                            <td class="border px-3 py-2">{{ tag.id }}</td>
                            <td class="border px-3 py-2">
                                <span
                                    class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-sky-700"
                                >
                                    {{ tag.name }}
                                </span>
                            </td>
                            <td class="border px-3 py-2">
                                {{ tag.questions_count }}
                            </td>
                            <td class="border px-3 py-2">
                                <button
                                    type="button"
                                    class="text-red-600 underline disabled:opacity-50"
                                    :disabled="deletingId === tag.id"
                                    @click="remove(tag)"
                                >
                                    {{
                                        deletingId === tag.id
                                            ? '删除中...'
                                            : '删除'
                                    }}
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type QuestionTagItem = {
    id: number;
    name: string;
    questions_count: number;
};

const page = usePage();
const tags = computed(
    () => (page.props.tags as QuestionTagItem[] | undefined) ?? [],
);
const deletingId = ref<number | null>(null);
const deleteError = ref('');

const form = useForm({
    name: '',
});

function submit(): void {
    deleteError.value = '';

    form.post('/admin/question-tags', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

function remove(tag: QuestionTagItem): void {
    if (!confirm(`确定要删除标签“${tag.name}”吗？`)) {
        return;
    }

    deleteError.value = '';
    deletingId.value = tag.id;

    router.delete(`/admin/question-tags/${tag.id}`, {
        preserveScroll: true,
        onError: (errors) => {
            deleteError.value = String(
                errors.delete ?? '删除失败，请稍后重试。',
            );
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>
