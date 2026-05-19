<template>
    <div class="space-y-6 p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h1 class="mb-4 text-2xl font-bold">题库管理</h1>
            <form @submit.prevent="submit" class="grid gap-4 md:grid-cols-2">
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
                        <label class="block text-sm font-medium">题目标签</label>
                        <a href="/admin/question-tags" class="text-sm text-blue-600 underline">管理标签</a>
                    </div>

                    <div v-if="availableTags.length > 0" class="flex flex-wrap gap-2 rounded border border-slate-200 bg-slate-50 p-3">
                        <label
                            v-for="tag in availableTags"
                            :key="tag.id"
                            class="cursor-pointer"
                        >
                            <input v-model="form.tags" type="checkbox" :value="tag.name" class="peer sr-only" />
                            <span class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-sm text-slate-700 transition peer-checked:border-sky-600 peer-checked:bg-sky-600 peer-checked:text-white">
                                {{ tag.name }}
                            </span>
                        </label>
                    </div>
                    <div v-else class="rounded border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700">
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

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                    >
                        {{ form.processing ? '提交中...' : editingId ? '保存修改' : '新建题目' }}
                    </button>
                    <button
                        v-if="editingId"
                        type="button"
                        class="ml-2 rounded border px-4 py-2 text-slate-700"
                        @click="cancelEdit"
                    >
                        取消编辑
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-xl font-semibold">Excel/CSV 导入题目</h2>
            <a
                href="/admin/questions/import/template"
                class="mb-3 inline-block text-sm text-blue-600 underline"
            >
                下载导入模板（含示例）
            </a>
            <form class="flex flex-col gap-3 sm:flex-row sm:items-center" @submit.prevent="importFile">
                <input
                    ref="fileInputRef"
                    type="file"
                    accept=".csv,.xlsx"
                    class="w-full rounded border px-3 py-2 text-sm sm:w-auto"
                    @change="onFileChange"
                />
                <button
                    type="submit"
                    :disabled="importing"
                    class="rounded bg-emerald-600 px-4 py-2 text-white disabled:opacity-50"
                >
                    {{ importing ? '导入中...' : '上传并导入' }}
                </button>
            </form>
            <p class="mt-2 text-xs text-gray-500">
                推荐模板列：序号,题目,选项 A,选项 B,选项 C,选项 D,正确项,解析。标签/类型/难度/启用可按需填写。
            </p>
            <p v-if="importMessage" class="mt-2 text-sm text-emerald-700">{{ importMessage }}</p>
            <p v-if="importError" class="mt-2 text-sm text-red-600">{{ importError }}</p>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h2 class="mb-3 text-xl font-semibold">题目列表</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full border text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="border px-3 py-2 text-left">ID</th>
                            <th class="border px-3 py-2 text-left">内容</th>
                            <th class="border px-3 py-2 text-left">类型</th>
                            <th class="border px-3 py-2 text-left">选项</th>
                            <th class="border px-3 py-2 text-left">答案</th>
                            <th class="border px-3 py-2 text-left">标签</th>
                            <th class="border px-3 py-2 text-left">评价统计</th>
                            <th class="border px-3 py-2 text-left">纠错反馈</th>
                            <th class="border px-3 py-2 text-left">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="question in questions" :key="question.id">
                            <td class="border px-3 py-2">{{ question.id }}</td>
                            <td class="border px-3 py-2">{{ question.content }}</td>
                            <td class="border px-3 py-2">{{ question.type }}</td>
                            <td class="border px-3 py-2">{{ formatOptions(question.options) }}</td>
                            <td class="border px-3 py-2">{{ question.answer || '-' }}</td>
                            <td class="border px-3 py-2">{{ formatTags(question.tags) }}</td>
                            <td class="border px-3 py-2 text-xs text-slate-600">
                                <div>喜欢：{{ question.likes_count ?? 0 }}</div>
                                <div>不喜欢：{{ question.dislikes_count ?? 0 }}</div>
                            </td>
                            <td class="border px-3 py-2 text-xs text-slate-600">
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
                            <td class="border px-3 py-2">
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
import { router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

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

const page = usePage();
const questions = computed(
    () => (page.props.questions as QuestionItem[] | undefined) ?? [],
);
const availableTags = computed(
    () => (page.props.availableTags as TagOption[] | undefined) ?? [],
);
const deletingId = ref<number | null>(null);
const editingId = ref<number | null>(null);
const selectedFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const importing = ref(false);
const importMessage = ref('');
const importError = ref('');

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

function submit(): void {
    const normalizedTags = Array.from(new Set(form.tags.map((tag) => tag.trim()).filter(Boolean)));

    const payload = {
        content: form.content,
        type: form.type,
        answer: form.answer || null,
        options: form.optionsText
            ? form.optionsText.split(',').map((s) => s.trim()).filter(Boolean)
            : [],
        tags: normalizedTags,
        explanation: form.explanation || null,
        difficulty: Number(form.difficulty) || 1,
    };

    if (editingId.value) {
        form.transform(() => payload).put(
            adminQuestions.update.url({ question: editingId.value }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    cancelEdit();
                },
            },
        );
        return;
    }

    form.transform(() => payload).post(adminQuestions.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.type = 'single';
            form.tags = [];
            form.difficulty = 1;
        },
    });
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

function cancelEdit(): void {
    editingId.value = null;
    form.reset();
    form.type = 'single';
    form.tags = [];
    form.difficulty = 1;
}

function remove(question: QuestionItem): void {
    if (confirm('确定要删除该题目吗？')) {
        deletingId.value = question.id;
        router.delete(
            adminQuestions.destroy.url({ question: question.id }),
            {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => {
                    deletingId.value = null;
                },
            },
        );
    }
}

function onFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    selectedFile.value = target.files?.[0] ?? null;
    importMessage.value = '';
    importError.value = '';
}

async function importFile(): Promise<void> {
    importMessage.value = '';
    importError.value = '';

    if (!selectedFile.value) {
        importError.value = '请先选择 CSV 或 XLSX 文件。';
        return;
    }

    importing.value = true;
    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);
        const response = await axios.post(adminQuestions.import.url(), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        importMessage.value = response.data?.message || '导入成功';
        selectedFile.value = null;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
        router.reload({ only: ['questions'] });
    } catch (e: unknown) {
        importError.value =
            (e as { response?: { data?: { message?: string } } })?.response?.data
                ?.message || '导入失败，请检查文件格式。';
    } finally {
        importing.value = false;
    }
}

function formatOptions(options: string[] | null): string {
    if (!options || options.length === 0) {
        return '-';
    }

    return options.join(', ');
}

function formatTags(tags: string[] | null): string {
    if (!tags || tags.length === 0) {
        return '-';
    }

    return tags.join(', ');
}

</script>
