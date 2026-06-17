<template>
    <div class="space-y-6 p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">管理题目</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        按分类筛选题目，并根据意见数量或点赞数快速排序查看。
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/questions/create"
                        class="inline-flex rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white"
                    >
                        新增题目
                    </Link>
                    <Link
                        href="/admin/question-tags"
                        class="inline-flex rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                    >
                        管理标签
                    </Link>
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_auto] lg:items-end">
                <div>
                    <label class="mb-1 block text-sm font-medium">按分类筛选</label>
                    <select v-model="selectedTag" class="w-full rounded border px-3 py-2">
                        <option value="">全部分类</option>
                        <option v-for="tag in availableTags" :key="tag.id" :value="tag.name">
                            {{ tag.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">排序方式</label>
                    <select v-model="selectedSort" class="w-full rounded border px-3 py-2 lg:min-w-56">
                        <option value="latest">按最新创建</option>
                        <option value="feedback_desc">按意见数量排序</option>
                        <option value="likes_desc">按 Likes 排序</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded bg-slate-900 px-4 py-2 text-sm font-medium text-white"
                        @click="applyFilters"
                    >
                        应用筛选
                    </button>
                    <button
                        type="button"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                        @click="resetFilters"
                    >
                        重置
                    </button>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-500">
                当前共 {{ questions.length }} 道题目
                <span v-if="selectedTag">，分类：{{ selectedTag }}</span>
            </p>
        </section>

        <section v-if="editingId" class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-semibold">编辑题目</h2>
                    <p class="mt-1 text-sm text-slate-500">修改完成后会保留当前筛选条件。</p>
                </div>
                <button
                    type="button"
                    class="rounded border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700"
                    @click="cancelEdit"
                >
                    取消编辑
                </button>
            </div>

            <form @submit.prevent="submitEdit" class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">题目内容</label>
                    <input
                        v-model="form.content"
                        required
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">
                        {{ form.errors.content }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">类型</label>
                    <select v-model="form.type" class="w-full rounded border px-3 py-2">
                        <option value="single">单选</option>
                        <option value="multiple">多选</option>
                        <option value="text">问答</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">
                        {{ form.errors.type }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">答案</label>
                    <input
                        v-model="form.answer"
                        class="w-full rounded border px-3 py-2"
                    />
                    <p v-if="form.errors.answer" class="mt-1 text-sm text-red-600">
                        {{ form.errors.answer }}
                    </p>
                </div>

                <div class="md:col-span-2">
                    <div class="mb-1 flex items-center justify-between gap-3">
                        <label class="block text-sm font-medium">题目分类 / 标签</label>
                        <Link href="/admin/question-tags" class="text-sm text-blue-600 underline">
                            管理标签
                        </Link>
                    </div>

                    <div
                        v-if="availableTags.length > 0"
                        class="flex flex-wrap gap-2 rounded border border-slate-200 bg-slate-50 p-3"
                    >
                        <label
                            v-for="tag in availableTags"
                            :key="tag.id"
                            class="cursor-pointer"
                        >
                            <input
                                v-model="form.tags"
                                type="checkbox"
                                :value="tag.name"
                                class="peer sr-only"
                            />
                            <span class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-sm text-slate-700 transition peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white">
                                {{ tag.name }}
                            </span>
                        </label>
                    </div>
                    <div
                        v-else
                        class="rounded border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700"
                    >
                        暂无可选标签，请先前往“管理标签”页面添加。
                    </div>
                    <p v-if="form.errors.tags" class="mt-1 text-sm text-red-600">
                        {{ form.errors.tags }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">难度（1-5）</label>
                    <input
                        v-model.number="form.difficulty"
                        type="number"
                        min="1"
                        max="5"
                        class="w-full rounded border px-3 py-2"
                    />
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">题目解释</label>
                    <textarea
                        v-model="form.explanation"
                        rows="2"
                        class="w-full rounded border px-3 py-2"
                        placeholder="答错后显示的解释"
                    ></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium">选项（逗号分隔）</label>
                    <input
                        v-model="form.optionsText"
                        placeholder="A选项, B选项, C选项"
                        class="w-full rounded border px-3 py-2"
                    />
                    <p class="mt-1 text-xs text-gray-500">问答题可留空该字段。</p>
                    <p v-if="optionsError" class="mt-1 text-sm text-red-600">
                        {{ optionsError }}
                    </p>
                </div>

                <div class="md:col-span-2 flex flex-wrap gap-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                    >
                        {{ form.processing ? '保存中...' : '保存修改' }}
                    </button>
                    <button
                        type="button"
                        class="rounded border border-slate-300 px-4 py-2 text-slate-700"
                        @click="cancelEdit"
                    >
                        取消
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold">题目列表</h2>
                <span class="text-sm text-slate-500">支持按分类筛选与按统计排序</span>
            </div>

            <div v-if="questions.length === 0" class="rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">
                当前条件下暂无题目，试试调整分类或排序条件。
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border px-3 py-2 text-left">ID</th>
                            <th class="border px-3 py-2 text-left">内容</th>
                            <th class="border px-3 py-2 text-left">类型</th>
                            <th class="border px-3 py-2 text-left">难度</th>
                            <th class="border px-3 py-2 text-left">分类</th>
                            <th class="border px-3 py-2 text-left">评价统计</th>
                            <th class="border px-3 py-2 text-left">纠错反馈</th>
                            <th class="border px-3 py-2 text-left">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="question in questions" :key="question.id">
                            <td class="border px-3 py-2 align-top">{{ question.id }}</td>
                            <td class="border px-3 py-2 align-top">
                                <p class="font-medium text-slate-900">{{ question.content }}</p>
                                <p class="mt-1 text-xs text-slate-500">
                                    答案：{{ question.answer || '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    选项：{{ formatOptions(question.options) }}
                                </p>
                            </td>
                            <td class="border px-3 py-2 align-top">{{ question.type }}</td>
                            <td class="border px-3 py-2 align-top">{{ question.difficulty || 1 }}</td>
                            <td class="border px-3 py-2 align-top">
                                <div v-if="question.tags?.length" class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="tag in question.tags"
                                        :key="`${question.id}-${tag}`"
                                        class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs text-sky-700"
                                    >
                                        {{ tag }}
                                    </span>
                                </div>
                                <span v-else class="text-slate-400">-</span>
                            </td>
                            <td class="border px-3 py-2 align-top text-xs text-slate-600">
                                <div>Likes：{{ question.likes_count ?? 0 }}</div>
                                <div>不喜欢：{{ question.dislikes_count ?? 0 }}</div>
                                <div>意见：{{ question.feedback_count ?? 0 }}</div>
                            </td>
                            <td class="border px-3 py-2 align-top text-xs text-slate-600">
                                <div v-if="question.feedback?.length > 0" class="space-y-2">
                                    <div
                                        v-for="feedback in question.feedback"
                                        :key="feedback.id"
                                        class="rounded border border-amber-200 bg-amber-50 p-2"
                                    >
                                        <p class="font-medium text-slate-700">
                                            {{ feedback.user?.name || `用户 #${feedback.user_id}` }}
                                        </p>
                                        <p class="mt-1">{{ feedback.correction_text }}</p>
                                        <p class="mt-1 text-[11px] text-slate-500">
                                            状态：{{ feedback.correction_status || 'pending' }}
                                        </p>
                                    </div>
                                </div>
                                <span v-else>-</span>
                            </td>
                            <td class="border px-3 py-2 align-top">
                                <button
                                    type="button"
                                    class="mr-3 text-blue-600 underline"
                                    @click="edit(question)"
                                >
                                    编辑
                                </button>
                                <button
                                    type="button"
                                    :disabled="deletingId === question.id"
                                    @click="remove(question)"
                                    class="text-red-600 underline disabled:opacity-50"
                                >
                                    {{ deletingId === question.id ? '删除中...' : '删除' }}
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
import adminQuestions from '@/routes/admin/questions';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type QuestionItem = {
    id: number;
    content: string;
    type: string;
    options: string[] | null;
    answer: string | null;
    explanation: string | null;
    option_explanations: Record<string, string> | null;
    tags: string[] | null;
    difficulty: number;
    likes_count?: number;
    dislikes_count?: number;
    feedback_count?: number;
    feedback: Array<{
        id: number;
        user_id: number;
        correction_text: string | null;
        correction_status: string | null;
        user?: {
            name?: string | null;
        } | null;
    }>;
};

type TagOption = {
    id: number;
    name: string;
};

type QuestionFilters = {
    tag?: string;
    sort?: 'latest' | 'likes_desc' | 'feedback_desc';
};

const page = usePage();
const questions = computed(
    () => (page.props.questions as QuestionItem[] | undefined) ?? [],
);
const availableTags = computed(
    () => (page.props.availableTags as TagOption[] | undefined) ?? [],
);
const filters = computed(
    () => (page.props.filters as QuestionFilters | undefined) ?? {},
);
const deletingId = ref<number | null>(null);
const editingId = ref<number | null>(null);
const selectedTag = ref('');
const selectedSort = ref<'latest' | 'likes_desc' | 'feedback_desc'>('latest');

watch(
    filters,
    (value) => {
        selectedTag.value = value.tag ?? '';
        selectedSort.value = value.sort ?? 'latest';
    },
    { immediate: true, deep: true },
);

const form = useForm({
    content: '',
    type: 'single',
    optionsText: '',
    answer: '',
    tags: [] as string[],
    explanation: '',
    difficulty: 1,
});

const optionsError = computed(() => {
    return (form.errors as Record<string, string | undefined>).options;
});

function applyFilters(): void {
    router.get(
        adminQuestions.index.url(),
        {
            tag: selectedTag.value || undefined,
            sort: selectedSort.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    selectedTag.value = '';
    selectedSort.value = 'latest';
    applyFilters();
}

function edit(question: QuestionItem): void {
    editingId.value = question.id;
    form.content = question.content;
    form.type = question.type;
    form.answer = question.answer || '';
    form.optionsText = question.options?.join(', ') || '';
    form.tags = [...(question.tags ?? [])];
    form.explanation = question.explanation || '';
    form.difficulty = question.difficulty || 1;
}

function submitEdit(): void {
    if (!editingId.value) {
        return;
    }

    const normalizedTags = Array.from(new Set(form.tags.map((tag) => tag.trim()).filter(Boolean)));

    form.transform(() => ({
        content: form.content,
        type: form.type,
        answer: form.answer || null,
        options: form.optionsText
            ? form.optionsText.split(',').map((item) => item.trim()).filter(Boolean)
            : [],
        tags: normalizedTags,
        explanation: form.explanation || null,
        difficulty: Number(form.difficulty) || 1,
    })).put(adminQuestions.update.url({ question: editingId.value }), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEdit();
        },
    });
}

function cancelEdit(): void {
    editingId.value = null;
    form.reset();
    form.type = 'single';
    form.tags = [];
    form.difficulty = 1;
}

function remove(question: QuestionItem): void {
    if (!confirm('确定要删除该题目吗？')) {
        return;
    }

    deletingId.value = question.id;
    router.delete(adminQuestions.destroy.url({ question: question.id }), {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function formatOptions(options: string[] | null): string {
    if (!options || options.length === 0) {
        return '-';
    }

    return options.join(', ');
}

</script>
