<template>
    <div class="p-6">
        <h1 class="mb-4 text-2xl font-bold">问卷模板管理</h1>
        <div class="mb-4">
            <router-link
                :to="route('admin.survey_templates.create')"
                class="rounded bg-blue-600 px-4 py-2 text-white"
                >新建模板</router-link
            >
        </div>
        <table class="min-w-full border">
            <thead>
                <tr>
                    <th class="border px-2 py-1">ID</th>
                    <th class="border px-2 py-1">名称</th>
                    <th class="border px-2 py-1">描述</th>
                    <th class="border px-2 py-1">状态</th>
                    <th class="border px-2 py-1">操作</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="template in templates.data" :key="template.id">
                    <td class="border px-2 py-1">{{ template.id }}</td>
                    <td class="border px-2 py-1">{{ template.name }}</td>
                    <td class="border px-2 py-1">{{ template.description }}</td>
                    <td class="border px-2 py-1">
                        {{ template.status ? '启用' : '禁用' }}
                    </td>
                    <td class="border px-2 py-1">
                        <router-link
                            :to="
                                route('admin.survey_templates.edit', {
                                    survey_template: template.id,
                                })
                            "
                            class="mr-2 text-blue-600 underline"
                            >编辑</router-link
                        >
                        <router-link
                            :to="
                                route(
                                    'admin.survey_templates.questions.index',
                                    { survey_template: template.id },
                                )
                            "
                            class="mr-2 text-green-600 underline"
                            >题目管理</router-link
                        >
                        <button
                            @click="remove(template)"
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
const templates = page.props.templates;

function remove(template: any) {
    if (confirm('确定要删除该模板吗？')) {
        router.delete(
            route('admin.survey_templates.destroy', {
                survey_template: template.id,
            }),
            {
                onSuccess: () => window.location.reload(),
            },
        );
    }
}
</script>
