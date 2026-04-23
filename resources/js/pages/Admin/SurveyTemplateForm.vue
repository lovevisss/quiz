<template>
    <div class="mx-auto max-w-xl p-6">
        <h1 class="mb-4 text-2xl font-bold">
            {{ template ? '编辑模板' : '新建模板' }}
        </h1>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block">名称</label>
                <input
                    v-model="form.name"
                    required
                    class="w-full rounded border px-2 py-1"
                />
            </div>
            <div>
                <label class="block">描述</label>
                <textarea
                    v-model="form.description"
                    class="w-full rounded border px-2 py-1"
                    rows="2"
                ></textarea>
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
const template = page.props.template || null;
const form = ref({
    name: template ? template.name : '',
    description: template ? template.description : '',
    status: template ? !!template.status : true,
});

function submit() {
    const payload = {
        name: form.value.name,
        description: form.value.description,
        status: form.value.status,
    };
    if (template) {
        router.put(
            route('admin.survey_templates.update', {
                survey_template: template.id,
            }),
            payload,
            {
                onSuccess: () =>
                    (window.location.href = route(
                        'admin.survey_templates.index',
                    )),
            },
        );
    } else {
        router.post(route('admin.survey_templates.store'), payload, {
            onSuccess: () =>
                (window.location.href = route('admin.survey_templates.index')),
        });
    }
}
</script>
