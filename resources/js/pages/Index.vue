<script setup lang="ts">
import ConditionRender from '@/components/ConditionRender.vue';
import Nav from '@/components/Nav.vue';
import { RouterView } from 'vue-router';
</script>

<template>
    <div class="flex h-screen flex-1 flex-col overflow-y-hidden">
        <Nav />
        <RouterView />
        <condition-render></condition-render>
    </div>
</template>

<style scoped></style>
