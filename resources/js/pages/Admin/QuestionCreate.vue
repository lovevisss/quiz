<script setup lang="ts">
import adminQuestions from '@/routes/admin/questions';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, ref } from 'vue';

type TagOption = {
    id: number;
    name: string;
};

const page = usePage();
const availableTags = computed(
    () => (page.props.availableTags as TagOption[] | undefined) ?? [],
);
const selectedFile = ref<File | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const importing = ref(false);
const importMessage = ref('');
const importError = ref('');

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

const optionsArray = computed(() =>
    form.optionsText
        .split('\n')
        .map((item) => item.trim())
        .filter(Boolean),
);

function submit(): void {
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
    })).post(adminQuestions.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.type = 'single';
            form.tags = [];
            form.difficulty = 1;
            form.status = true;
        },
    });
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

        const response = await axios.post(
            adminQuestions.import.url(),
            formData,
            {
                headers: { 'Content-Type': 'multipart/form-data' },
            },
        );

        importMessage.value =
            response.data?.message || '导入成功。请回到题库列表查看结果。';
        selectedFile.value = null;
        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    } catch (error: unknown) {
        importError.value =
            (error as { response?: { data?: { message?: string } } })?.response
                ?.data?.message ||
            '导入失败，请检查文件格式、必填列和答案格式。';
    } finally {
        importing.value = false;
    }
}
</script>

<template>
    <div class="space-y-5 p-4 sm:p-6">
        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <h1 class="text-2xl font-bold text-slate-950">新增题目</h1>
                    <p class="mt-1 text-sm text-slate-600">
                        录入单选、多选、问答题，也可以通过模板批量导入。
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/admin/questions"
                        class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                        >题库列表</Link
                    >
                    <Link
                        href="/admin/question-tags"
                        class="rounded border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700"
                        >管理标签</Link
                    >
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <h2 class="text-xl font-bold text-slate-950">题目表单</h2>
            <form
                class="mt-4 grid gap-4 md:grid-cols-2"
                @submit.prevent="submit"
            >
                <label
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    题目内容
                    <textarea
                        v-model="form.content"
                        required
                        rows="4"
                        class="mt-1 w-full rounded border px-3 py-2"
                        placeholder="输入题干"
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
                    标准答案
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
                    <span
                        v-if="form.errors.answer"
                        class="mt-1 block text-sm text-red-600"
                        >{{ form.errors.answer }}</span
                    >
                </label>

                <label
                    v-if="form.type !== 'text'"
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    选项，每行一个，系统会按 A/B/C/D 标记
                    <textarea
                        v-model="form.optionsText"
                        rows="5"
                        class="mt-1 w-full rounded border px-3 py-2"
                        placeholder="启用双重验证&#10;使用弱密码&#10;共享账号&#10;关闭安全提醒"
                    ></textarea>
                    <span
                        v-if="form.errors.options"
                        class="mt-1 block text-sm text-red-600"
                        >{{ form.errors.options }}</span
                    >
                </label>

                <div class="md:col-span-2">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <p class="text-sm font-medium text-slate-700">
                            题目标签
                        </p>
                        <Link
                            href="/admin/question-tags"
                            class="text-sm font-semibold text-emerald-700"
                            >维护标签</Link
                        >
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
                            <span
                                class="inline-flex rounded-full border border-slate-300 bg-white px-3 py-1 text-sm text-slate-700 peer-checked:border-emerald-600 peer-checked:bg-emerald-600 peer-checked:text-white"
                                >{{ tag.name }}</span
                            >
                        </label>
                    </div>
                    <div
                        v-else
                        class="rounded border border-dashed border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-700"
                    >
                        暂无可选标签，可以先保存题目，或前往“管理标签”添加。
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
                    保存后立即启用
                </label>

                <label
                    class="block text-sm font-medium text-slate-700 md:col-span-2"
                >
                    题目解析
                    <textarea
                        v-model="form.explanation"
                        rows="3"
                        class="mt-1 w-full rounded border px-3 py-2"
                        placeholder="答错后展示的解析"
                    ></textarea>
                </label>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                    >
                        {{ form.processing ? '保存中...' : '保存题目' }}
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-lg border bg-white p-4 shadow-sm">
            <div
                class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between"
            >
                <div>
                    <h2 class="text-xl font-bold text-slate-950">批量导入</h2>
                    <p class="mt-1 text-sm leading-6 text-slate-600">
                        推荐使用模板列：序号、题目、选项 A、选项 B、选项 C、选项
                        D、正确项、解析、标签、类型、难度、启用。
                    </p>
                </div>
                <a
                    href="/admin/questions/import/template"
                    class="text-sm font-semibold text-emerald-700"
                    >下载导入模板</a
                >
            </div>

            <form
                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center"
                @submit.prevent="importFile"
            >
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
                    class="rounded bg-slate-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                >
                    {{ importing ? '导入中...' : '上传并导入' }}
                </button>
            </form>
            <p
                v-if="importMessage"
                class="mt-3 rounded bg-emerald-50 px-3 py-2 text-sm text-emerald-700"
            >
                {{ importMessage }}
            </p>
            <p
                v-if="importError"
                class="mt-3 rounded bg-rose-50 px-3 py-2 text-sm text-rose-700"
            >
                {{ importError }}
            </p>
        </section>
    </div>
</template>
