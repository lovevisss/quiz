<template>
    <div
        data-testid="quiz-result"
        class="flex flex-col items-center space-y-4 p-4"
    >
        <h1 class="text-2xl font-bold">Your Score</h1>
        <p class="text-lg">You scored 8 out of 10</p>
        <div class="fixed-footer">
            <button class="rounded-lg bg-blue-500 px-6 py-3 text-white">
                Retry
            </button>
            <button class="ml-2 rounded-lg bg-green-500 px-6 py-3 text-white">
                View Leaderboard
            </button>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'QuizResult',
};
</script>

<style scoped>
.fixed-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background-color: white;
    box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
}
</style>
