<script setup lang="ts">
import adminPaperStrategies from '@/routes/admin/paper_strategies';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Strategy = {
    id: number;
    name: string;
    mode: 'fixed' | 'random';
    config: {
        question_ids?: number[];
        count?: number;
        tag_ratios?: Record<string, number>;
    } | null;
    status: boolean;
};

type QuestionOption = {
    id: number;
    content: string;
    type: string;
    tags: string[] | null;
};

type TagOption = {
    id: number;
    name: string;
};

const page = usePage();
const strategy = (page.props.strategy as Strategy | undefined) ?? null;
const questions = computed(
    () => (page.props.questions as QuestionOption[] | undefined) ?? [],
);
const tags = computed(() => (page.props.tags as TagOption[] | undefined) ?? []);

const fixedQuestionIds = ref<number[]>([
    ...(strategy?.config?.question_ids ?? []),
]);
const tagRatios = ref<Record<string, number>>(
    Object.fromEntries(
        tags.value.map((tag) => [
            tag.name,
            Math.round(
                Number(strategy?.config?.tag_ratios?.[tag.name] ?? 0) * 100,
            ),
        ]),
    ),
);

const form = useForm({
    name: strategy?.name ?? '',
    mode: strategy?.mode ?? 'fixed',
    count: Number(strategy?.config?.count ?? 10),
    status: strategy ? Boolean(strategy.status) : true,
});

const selectedQuestions = computed(
    () =>
        fixedQuestionIds.value
            .map((id) => questions.value.find((question) => question.id === id))
            .filter(Boolean) as QuestionOption[],
);

const configPreview = computed(() => {
    if (form.mode === 'fixed') {
        return {
            question_ids: fixedQuestionIds.value,
            count: fixedQuestionIds.value.length,
        };
    }

    return {
        count: Number(form.count) || 10,
        tag_ratios: Object.fromEntries(
            Object.entries(tagRatios.value)
                .filter(([, value]) => Number(value) > 0)
                .map(([tag, value]) => [tag, Number(value) / 100]),
        ),
    };
});

function toggleQuestion(questionId: number): void {
    fixedQuestionIds.value = fixedQuestionIds.value.includes(questionId)
        ? fixedQuestionIds.value.filter((id) => id !== questionId)
        : [...fixedQuestionIds.value, questionId];
}

function moveQuestion(questionId: number, direction: -1 | 1): void {
    const index = fixedQuestionIds.value.indexOf(questionId);
    const targetIndex = index + direction;
    if (
        index < 0 ||
        targetIndex < 0 ||
        targetIndex >= fixedQuestionIds.value.length
    ) {
        return;
    }

    const next = [...fixedQuestionIds.value];
    [next[index], next[targetIndex]] = [next[targetIndex], next[index]];
    fixedQuestionIds.value = next;
}

function submit(): void {
    form.transform(() => ({
        name: form.name,
        mode: form.mode,
        config: configPreview.value,
        status: form.status,
    }));

    if (strategy) {
        form.put(
            adminPaperStrategies.update.url({ paper_strategy: strategy.id }),
        );
        return;
    }

    form.post(adminPaperStrategies.store.url());
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
                        {{ strategy ? '编辑试卷策略' : '新建试卷策略' }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-600">
                        使用可视化配置生成策略 JSON，减少手写配置出错。
                    </p>
                </div>
                <Link
                    :href="adminPaperStrategies.index().url"
                    class="rounded border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"
                >
                    返回列表
                </Link>
            </div>
        </section>

        <form
            class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]"
            @submit.prevent="submit"
        >
            <section class="space-y-4 rounded-lg border bg-white p-4 shadow-sm">
                <label class="block text-sm font-medium text-slate-700">
                    策略名称
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
                    组卷模式
                    <select
                        v-model="form.mode"
                        class="mt-1 w-full rounded border px-3 py-2"
                    >
                        <option value="fixed">固定题组</option>
                        <option value="random">随机组卷</option>
                    </select>
                </label>

                <div v-if="form.mode === 'fixed'" class="space-y-4">
                    <div
                        class="rounded border border-slate-200 bg-slate-50 p-3"
                    >
                        <p class="text-sm font-semibold text-slate-900">
                            已选题目顺序
                        </p>
                        <div
                            v-if="selectedQuestions.length === 0"
                            class="mt-2 text-sm text-slate-500"
                        >
                            尚未选择题目。
                        </div>
                        <ol v-else class="mt-2 space-y-2">
                            <li
                                v-for="(question, index) in selectedQuestions"
                                :key="question.id"
                                class="flex items-start justify-between gap-2 rounded bg-white p-3 text-sm"
                            >
                                <span class="min-w-0"
                                    >{{ index + 1 }}. #{{ question.id }}
                                    {{ question.content }}</span
                                >
                                <span class="flex shrink-0 gap-1">
                                    <button
                                        type="button"
                                        class="rounded border px-2 py-1 text-xs"
                                        @click="moveQuestion(question.id, -1)"
                                    >
                                        上移
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded border px-2 py-1 text-xs"
                                        @click="moveQuestion(question.id, 1)"
                                    >
                                        下移
                                    </button>
                                </span>
                            </li>
                        </ol>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            可选题目
                        </p>
                        <div
                            class="mt-2 grid max-h-[32rem] gap-2 overflow-auto rounded border border-slate-200 p-2"
                        >
                            <label
                                v-for="question in questions"
                                :key="question.id"
                                class="flex cursor-pointer items-start gap-3 rounded p-3 hover:bg-slate-50"
                            >
                                <input
                                    type="checkbox"
                                    :checked="
                                        fixedQuestionIds.includes(question.id)
                                    "
                                    class="mt-1"
                                    @change="toggleQuestion(question.id)"
                                />
                                <span class="min-w-0">
                                    <span
                                        class="block text-sm font-semibold text-slate-950"
                                        >#{{ question.id }}
                                        {{ question.content }}</span
                                    >
                                    <span
                                        class="mt-1 block text-xs text-slate-500"
                                        >{{ question.type }} ·
                                        {{
                                            (question.tags ?? []).join(' / ') ||
                                            '无标签'
                                        }}</span
                                    >
                                </span>
                            </label>
                        </div>
                    </div>
                </div>

                <div v-else class="space-y-4">
                    <label class="block text-sm font-medium text-slate-700">
                        抽题数量
                        <input
                            v-model.number="form.count"
                            type="number"
                            min="1"
                            max="50"
                            class="mt-1 w-full rounded border px-3 py-2"
                        />
                    </label>

                    <div>
                        <p class="text-sm font-semibold text-slate-900">
                            标签比例
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            填写百分比，例如 50 表示该标签约占 50%。留空或 0
                            表示不指定。
                        </p>
                        <div class="mt-3 grid gap-2">
                            <label
                                v-for="tag in tags"
                                :key="tag.id"
                                class="grid grid-cols-[minmax(0,1fr)_120px] items-center gap-3 rounded border border-slate-200 p-3"
                            >
                                <span
                                    class="text-sm font-medium text-slate-700"
                                    >{{ tag.name }}</span
                                >
                                <input
                                    v-model.number="tagRatios[tag.name]"
                                    type="number"
                                    min="0"
                                    max="100"
                                    class="rounded border px-3 py-2 text-sm"
                                />
                            </label>
                        </div>
                    </div>
                </div>

                <label
                    class="flex items-center gap-2 text-sm font-medium text-slate-700"
                >
                    <input v-model="form.status" type="checkbox" />
                    启用该策略
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-fit rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
                >
                    {{ form.processing ? '保存中...' : '保存策略' }}
                </button>
            </section>

            <aside class="h-fit rounded-lg border bg-white p-4 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">配置预览</h2>
                <p class="mt-1 text-sm text-slate-600">
                    提交时将写入 `paper_strategies.config`。
                </p>
                <pre
                    class="mt-4 max-h-[32rem] overflow-auto rounded bg-slate-950 p-4 text-xs text-slate-50"
                    >{{ JSON.stringify(configPreview, null, 2) }}</pre
                >
            </aside>
        </form>
    </div>
</template>
