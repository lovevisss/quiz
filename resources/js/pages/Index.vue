<script setup lang="ts">
import ConditionRender from '@/components/ConditionRender.vue';
import Nav from '@/components/Nav.vue';
import { RouterView } from 'vue-router';
</script>

<template>
    <div
        class="flex min-h-screen flex-1 flex-col overflow-x-hidden bg-slate-950 text-slate-50"
    >
        <Nav />
        <div class="flex min-h-0 flex-1 flex-col px-0">
            <RouterView />
        </div>
        <condition-render></condition-render>
    </div>
</template>

<style scoped></style>
