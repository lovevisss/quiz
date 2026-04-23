<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-bold">问卷题目管理</h1>
        <div class="mb-4">
            <router-link
                :to="
                    route('admin.survey_templates.questions.create', {
                        survey_template: surveyTemplate.id,
                    })
                "
                class="rounded bg-blue-600 px-4 py-2 text-white"
                >新建题目</router-link
            >
        </div>
        <table class="min-w-full border">
            <thead>
                <tr>
                    <th class="border px-2 py-1">ID</th>
                    <th class="border px-2 py-1">内容</th>
                    <th class="border px-2 py-1">类型</th>
                    <th class="border px-2 py-1">选项</th>
                    <th class="border px-2 py-1">必填</th>
                    <th class="border px-2 py-1">顺序</th>
                    <th class="border px-2 py-1">操作</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="question in questions.data" :key="question.id">
                    <td class="border px-2 py-1">{{ question.id }}</td>
                    <td class="border px-2 py-1">{{ question.content }}</td>
                    <td class="border px-2 py-1">{{ question.type }}</td>
                    <td class="border px-2 py-1">
                        {{
                            Array.isArray(question.options)
                                ? question.options.join(', ')
                                : question.options
                        }}
                    </td>
                    <td class="border px-2 py-1">
                        {{ question.required ? '是' : '否' }}
                    </td>
                    <td class="border px-2 py-1">{{ question.order }}</td>
                    <td class="border px-2 py-1">
                        <router-link
                            :to="
                                route('admin.survey_templates.questions.edit', {
                                    survey_template: surveyTemplate.id,
                                    question: question.id,
                                })
                            "
                            class="mr-2 text-blue-600 underline"
                            >编辑</router-link
                        >
                        <button
                            @click="remove(question)"
                            class="text-red-600 underline"
                        >
                            删除
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
const page = usePage();
const surveyTemplate = page.props.surveyTemplate;
const questions = page.props.questions;

function remove(question: any) {
    if (confirm('确定要删除该题目吗？')) {
        router.delete(
            route('admin.survey_templates.questions.destroy', {
                survey_template: surveyTemplate.id,
                question: question.id,
            }),
            {
                onSuccess: () => window.location.reload(),
            },
        );
    }
}
</script>
