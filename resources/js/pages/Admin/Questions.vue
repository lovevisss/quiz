<script setup lang="ts">
import adminQuestions from '@/routes/admin/questions';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type QuestionItem = {
    id: number;
    content: string;
    type: 'single' | 'multiple' | 'text';
    options: string[] | Record<string, string> | null;
    answer: string | null;
    explanation: string | null;
    tags: string[] | null;
    difficulty: number;
    status: boolean;
    likes_count?: number;
    dislikes_count?: number;
    feedback_count?: number;
    feedback: Array<{
        id: number;
        user_id: number;
        correction_text: string | null;
        correction_status: string | null;
        user?: { name?: string | null } | null;
    }>;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Paginator<T> = {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
};

type TagOption = {
    id: number;
    name: string;
};

type QuestionFilters = {
    q?: string;
    tag?: string;
    type?: string;
    status?: string;
    sort?: string;
};

const page = usePage();
const paginator = computed(
    () => page.props.questions as Paginator<QuestionItem>,
);
const questions = computed(() => paginator.value?.data ?? []);
const availableTags = computed(
    () => (page.props.availableTags as TagOption[] | undefined) ?? [],
);
const filters = computed(
    () => (page.props.filters as QuestionFilters | undefined) ?? {},
);

const deletingId = ref<number | null>(null);
const editingId = ref<number | null>(null);
const filterForm = ref({
    q: '',
    tag: '',
    type: '',
    status: '',
    sort: 'latest',
});

const form = useForm({
    content: '',
    type: 'single' as 'single' | 'multiple' | 'text',
    optionsText: '',
    answer: '',
    tags: [] as string[],
    explanation: '',
    difficulty: 1,
    status: true,
});

watch(
    filters,
    (value) => {
        filterForm.value = {
            q: value.q ?? '',
            tag: value.tag ?? '',
            type: value.type ?? '',
            status: value.status ?? '',
            sort: value.sort ?? 'latest',
        };
    },
    { immediate: true, deep: true },
);

const optionsArray = computed(() => {
    if (!form.optionsText.trim()) {
        return [];
    }

    return form.optionsText
        .split('\n')
        .map((item) => item.trim())
        .filter(Boolean);
});

function applyFilters(): void {
    router.get(
        adminQuestions.index.url(),
        {
            q: filterForm.value.q || undefined,
            tag: filterForm.value.tag || undefined,
            type: filterForm.value.type || undefined,
            status: filterForm.value.status || undefined,
            sort: filterForm.value.sort || undefined,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    filterForm.value = {
        q: '',
        tag: '',
        type: '',
        status: '',
        sort: 'latest',
    };
    applyFilters();
}

function edit(question: QuestionItem): void {
    editingId.value = question.id;
    form.content = question.content;
    form.type = question.type;
    form.answer = question.answer || '';
    form.optionsText = formatOptionsForEdit(question.options);
    form.tags = [...(question.tags ?? [])];
    form.explanation = question.explanation || '';
    form.difficulty = question.difficulty || 1;
    form.status = Boolean(question.status);
}

function submitEdit(): void {
    if (!editingId.value) {
        return;
    }

    const normalizedTags = Array.from(
        new Set(form.tags.map((tag) => tag.trim()).filter(Boolean)),
    );

    form.transform(() => ({
        content: form.content,
        type: form.type,
        answer: form.answer || null,
        options: form.type === 'text' ? [] : optionsArray.value,
        tags: normalizedTags,
        explanation: form.explanation || null,
        difficulty: Number(form.difficulty) || 1,
        status: form.status,
    })).put(adminQuestions.update.url({ question: editingId.value }), {
        preserveScroll: true,
        onSuccess: cancelEdit,
    });
}

function cancelEdit(): void {
    editingId.value = null;
    form.reset();
    form.type = 'single';
    form.tags = [];
    form.difficulty = 1;
    form.status = true;
}

function remove(question: QuestionItem): void {
    if (!confirm(`确定删除题目 #${question.id} 吗？`)) {
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

function typeLabel(type: string): string {
    return type === 'multiple' ? '多选' : type === 'text' ? '问答' : '单选';
}

function formatOptions(options: QuestionItem['options']): string {
    if (!options) {
        return '-';
    }

    const values = Array.isArray(options) ? options : Object.values(options);
    return values.length > 0 ? values.join(' / ') : '-';
}

function formatOptionsForEdit(options: QuestionItem['options']): string {
    if (!options) {
        return '';
    }

    return (Array.isArray(options) ? options : Object.values(options)).join(
        '\n',
    );
}

function cleanPaginationLabel(label: string): string {
    return label.replace('&laquo;', '上一页').replace('&raquo;', '下一页');
}
</script>

<template>
    <div class="space-y-5 p-4 sm:p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">题库管理</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        筛选、编辑、处理反馈，并维护答题活动使用的题目。
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/questions/create"
                        class="rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        >新增题目</Link
                    >
                    <Link
                        href="/admin/question-tags"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        >管理标签</Link
                    >
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_160px_140px_140px_180px_auto] lg:items-end"
            >
                <label class="block text-sm font-medium text-slate-700">
                    关键词
                    <input
                        v-model="filterForm.q"
                        class="mt-1 w-full rounded border px-3 py-2"
                        placeholder="题干、答案、解析"
                        @keyup.enter="applyFilters"
                    />
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    标签
                    <select
                        v-model="filterForm.tag"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="">全部标签</option>
                        <option
                            v-for="tag in availableTags"
                            :key="tag.id"
                            :value="tag.name"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    题型
                    <select
                        v-model="filterForm.type"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="">全部题型</option>
                        <option value="single">单选</option>
                        <option value="multiple">多选</option>
                        <option value="text">问答</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    状态
                    <select
                        v-model="filterForm.status"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="">全部状态</option>
                        <option value="active">启用</option>
                        <option value="inactive">停用</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    排序
                    <select
                        v-model="filterForm.sort"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="latest">最新创建</option>
                        <option value="oldest">最早创建</option>
                        <option value="likes_desc">点赞最多</option>
                        <option value="feedback_desc">纠错最多</option>
                    </select>
                </label>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white"
                        @click="applyFilters"
                    >
                        应用
                    </button>
                    <button
                        type="button"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        @click="resetFilters"
                    >
                        重置
                    </button>
                </div>
            </div>
            <p class="mt-3 text-sm text-slate-500">
                当前显示 {{ paginator?.from ?? 0 }}-{{
                    paginator?.to ?? 0
                }}
                条，共 {{ paginator?.total ?? 0 }} 条。
            </p>
        </section>

        <section
            v-if="editingId"
            class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm"
        >
            <div
                class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold text-slate-950">
                        编辑题目 #{{ editingId }}
                    </h2>
                    <p class="text-sm text-slate-500">
                        题型切换后，选项输入区会自动调整。
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                    @click="cancelEdit"
                >
                    取消编辑
                </button>
            </div>

            <form
                class="grid gap-4 md:grid-cols-2"
                @submit.prevent="submitEdit"
            >
                <label
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    题目内容
                    <textarea
                        v-model="form.content"
                        required
                        rows="3"
                        class="mt-1 w-full rounded border px-3 py-2"
                    ></textarea>
                    <span
                        v-if="form.errors.content"
                        class="mt-1 block text-sm text-red-600"
                        >{{ form.errors.content }}</span
                    >
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    题型
                    <select
                        v-model="form.type"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="single">单选题</option>
                        <option value="multiple">多选题</option>
                        <option value="text">问答题</option>
                    </select>
                </label>
                <label class="block text-sm font-medium text-slate-700">
                    答案
                    <input
                        v-model="form.answer"
                        class="mt-1 w-full rounded border px-3 py-2"
                        :placeholder="
                            form.type === 'multiple'
                                ? '例如 A,C'
                                : form.type === 'single'
                                  ? '例如 A'
                                  : '文本答案'
                        "
                    />
                </label>
                <label
                    v-if="form.type !== 'text'"
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    选项，每行一个
                    <textarea
                        v-model="form.optionsText"
                        rows="5"
                        class="mt-1 w-full rounded border px-3 py-2"
                        placeholder="启用双重验证&#10;使用弱密码&#10;共享账号"
                    ></textarea>
                </label>
                <div class="md:col-span-2">
                    <p class="text-sm font-medium text-slate-700">标签</p>
                    <div
                        class="mt-2 flex flex-wrap gap-2 rounded border border-slate-200 bg-slate-50 p-3"
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
                            <span
                                class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-sm text-slate-700 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white"
                                >{{ tag.name }}</span
                            >
                        </label>
                    </div>
                </div>
                <label class="block text-sm font-medium text-slate-700">
                    难度 1-5
                    <input
                        v-model.number="form.difficulty"
                        type="number"
                        min="1"
                        max="5"
                        class="mt-1 w-full rounded border px-3 py-2"
                    />
                </label>
                <label
                    class="flex items-center gap-2 text-sm font-medium text-slate-700"
                >
                    <input v-model="form.status" type="checkbox" />
                    启用该题
                </label>
                <label
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    解析
                    <textarea
                        v-model="form.explanation"
                        rows="3"
                        class="mt-1 w-full rounded border px-3 py-2"
                    ></textarea>
                </label>
                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        {{ form.processing ? '保存中...' : '保存修改' }}
                    </button>
                    <button
                        type="button"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        @click="cancelEdit"
                    >
                        取消
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-xl font-bold text-slate-950">题目列表</h2>
                <span class="text-sm text-slate-500">每页 20 条</span>
            </div>

            <div
                v-if="questions.length === 0"
                class="rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
            >
                当前条件下暂无题目。
            </div>

            <div v-else class="grid gap-3 lg:hidden">
                <article
                    v-for="question in questions"
                    :key="question.id"
                    class="rounded-2xl border border-slate-200 p-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">
                                #{{ question.id }} ·
                                {{ typeLabel(question.type) }}
                            </p>
                            <h3
                                class="mt-1 text-base leading-6 font-semibold text-slate-950"
                            >
                                {{ question.content }}
                            </h3>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold"
                            :class="
                                question.status
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{ question.status ? '启用' : '停用' }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm text-slate-600">
                        答案：{{ question.answer || '-' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        选项：{{ formatOptions(question.options) }}
                    </p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        <span
                            v-for="tag in question.tags ?? []"
                            :key="`${question.id}-${tag}`"
                            class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600"
                            >{{ tag }}</span
                        >
                    </div>
                    <div class="mt-4 flex gap-3 text-sm">
                        <button
                            type="button"
                            class="font-semibold text-emerald-700"
                            @click="edit(question)"
                        >
                            编辑
                        </button>
                        <button
                            type="button"
                            class="font-semibold text-red-600 disabled:opacity-50"
                            :disabled="deletingId === question.id"
                            @click="remove(question)"
                        >
                            {{
                                deletingId === question.id
                                    ? '删除中...'
                                    : '删除'
                            }}
                        </button>
                    </div>
                </article>
            </div>

            <div v-if="questions.length > 0" class="hidden overflow-x-auto lg:block">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600">
                        <tr>
                            <th class="border px-3 py-2 text-left">ID</th>
                            <th class="border px-3 py-2 text-left">内容</th>
                            <th class="border px-3 py-2 text-left">题型</th>
                            <th class="border px-3 py-2 text-left">标签</th>
                            <th class="border px-3 py-2 text-left">统计</th>
                            <th class="border px-3 py-2 text-left">状态</th>
                            <th class="border px-3 py-2 text-left">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="question in questions" :key="question.id">
                            <td class="border px-3 py-2 align-top">
                                {{ question.id }}
                            </td>
                            <td class="max-w-xl border px-3 py-2 align-top">
                                <p class="font-semibold text-slate-950">
                                    {{ question.content }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    答案：{{ question.answer || '-' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    选项：{{ formatOptions(question.options) }}
                                </p>
                            </td>
                            <td class="border px-3 py-2 align-top">
                                {{ typeLabel(question.type) }}
                            </td>
                            <td class="border px-3 py-2 align-top">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="tag in question.tags ?? []"
                                        :key="`${question.id}-${tag}`"
                                        class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600"
                                        >{{ tag }}</span
                                    >
                                </div>
                            </td>
                            <td
                                class="border px-3 py-2 align-top text-xs text-slate-600"
                            >
                                <div>喜欢：{{ question.likes_count ?? 0 }}</div>
                                <div>
                                    不喜欢：{{ question.dislikes_count ?? 0 }}
                                </div>
                                <div>
                                    纠错：{{ question.feedback_count ?? 0 }}
                                </div>
                            </td>
                            <td class="border px-3 py-2 align-top">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="
                                        question.status
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-slate-100 text-slate-600'
                                    "
                                >
                                    {{ question.status ? '启用' : '停用' }}
                                </span>
                            </td>
                            <td class="border px-3 py-2 align-top">
                                <button
                                    type="button"
                                    class="mr-3 font-semibold text-emerald-700"
                                    @click="edit(question)"
                                >
                                    编辑
                                </button>
                                <button
                                    type="button"
                                    class="font-semibold text-red-600 disabled:opacity-50"
                                    :disabled="deletingId === question.id"
                                    @click="remove(question)"
                                >
                                    {{
                                        deletingId === question.id
                                            ? '删除中...'
                                            : '删除'
                                    }}
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="paginator?.links?.length"
                class="mt-4 flex flex-wrap gap-2"
            >
                <Link
                    v-for="link in paginator.links"
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
                    preserve-state
                >
                    {{ cleanPaginationLabel(link.label) }}
                </Link>
            </nav>
        </section>
    </div>
</template>
