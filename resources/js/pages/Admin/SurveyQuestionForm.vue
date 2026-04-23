<template>
    <div class="mx-auto max-w-xl p-6">
        <h1 class="mb-4 text-2xl font-bold">
            {{ question ? '编辑题目' : '新建题目' }}
        </h1>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block">内容</label>
                <input
                    v-model="form.content"
                    required
                    class="w-full rounded border px-2 py-1"
                />
            </div>
            <div>
                <label class="block">类型</label>
                <input
                    v-model="form.type"
                    required
                    class="w-full rounded border px-2 py-1"
                />
            </div>
            <div>
                <label class="block">选项（用逗号分隔）</label>
                <input
                    v-model="form.options"
                    class="w-full rounded border px-2 py-1"
                />
            </div>
            <div>
                <label class="block">必填</label>
                <select
                    v-model="form.required"
                    class="w-full rounded border px-2 py-1"
                >
                    <option :value="true">是</option>
                    <option :value="false">否</option>
                </select>
            </div>
            <div>
                <label class="block">顺序</label>
                <input
                    v-model.number="form.order"
                    type="number"
                    min="1"
                    class="w-full rounded border px-2 py-1"
                />
            </div>
            <button
                type="submit"
                class="rounded bg-blue-600 px-4 py-2 text-white"
            >
                保存
            </button>
        </form>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
const page = usePage();
const surveyTemplate = page.props.surveyTemplate;
const question = page.props.question || null;
const form = ref({
    content: question ? question.content : '',
    type: question ? question.type : '',
    options: question
        ? Array.isArray(question.options)
            ? question.options.join(',')
            : question.options
        : '',
    required: question ? !!question.required : false,
    order: question ? question.order : 1,
});

function submit() {
    const payload = {
        content: form.value.content,
        type: form.value.type,
        options: form.value.options
            ? form.value.options.split(',').map((opt) => opt.trim())
            : [],
        required: form.value.required,
        order: form.value.order,
    };
    if (question) {
        router.put(
            route('admin.survey_templates.questions.update', {
                survey_template: surveyTemplate.id,
                question: question.id,
            }),
            payload,
            {
                onSuccess: () =>
                    (window.location.href = route(
                        'admin.survey_templates.questions.index',
                        { survey_template: surveyTemplate.id },
                    )),
            },
        );
    } else {
        router.post(
            route('admin.survey_templates.questions.store', {
                survey_template: surveyTemplate.id,
            }),
            payload,
            {
                onSuccess: () =>
                    (window.location.href = route(
                        'admin.survey_templates.questions.index',
                        { survey_template: surveyTemplate.id },
                    )),
            },
        );
    }
}
</script>
