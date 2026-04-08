<script setup lang="ts">
import { computed, ref } from 'vue';

const guess = ref<number | null>(null);
const answer = Math.floor(Math.random() * 2) + 1;
const guessOK = computed(() => guess.value === answer);
</script>

<template>
    <p>
        Please enter a number (1 or 2):
        <input v-model.number="guess" type="number" min="1" max="2" />
    </p>
    <p v-if="guessOK">You guessed right!</p>
    <p v-else-if="guess === null">Type the number!</p>
    <p v-else>Sorry you can try again</p>
</template>

<style scoped></style>
