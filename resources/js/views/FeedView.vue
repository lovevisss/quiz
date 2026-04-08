<script setup lang="ts">
import { ref } from 'vue';

const msg = ref('234');
const rawHtml = ref('<span style="color: red">This is raw html</span>');
const disabled = ref(true);
const number = ref(11);
const isOk = ref(true);
const eventName = ref('mouseover');

function toggleDisabled() {
    disabled.value = !disabled.value;
}

function showMessage() {
    alert('danger');
}
</script>

<template>
    <div class="p-4">
        <p class="mb-2 text-lg font-semibold">Home feed</p>
        <p>Message: {{ msg }}</p>
        <p v-once>this will not change {{ msg }}</p>
        <button @click="toggleDisabled">Click me</button>

        <p>Output raw html : <span v-html="rawHtml"></span></p>
        <p>{{ rawHtml }}</p>

        <button :disabled="disabled" class="disabled:text-gray-600">
            Click me
        </button>

        <p>{{ number + 1 }}</p>
        <p>{{ isOk ? 'YES' : 'NO' }}</p>
        <a href="" v-on:[eventName]="showMessage">{{ eventName }}</a>
    </div>
</template>
