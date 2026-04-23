<template>
    <div data-testid="quiz-question-card" class="flex flex-col space-y-4 p-4">
        <div>
            <p class="text-lg font-medium">Question 1 of 10</p>
            <progress
                class="question-progress w-full"
                value="1"
                max="10"
            ></progress>
        </div>
        <div>
            <p class="text-xl">What is the capital of France?</p>
        </div>
        <div class="fixed-footer">
            <button class="rounded-lg bg-gray-300 px-6 py-3">Previous</button>
            <button class="rounded-lg bg-blue-500 px-6 py-3 text-white">
                Next
            </button>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'QuizQuestionCard',
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

.question-progress {
    height: 8px;
    border-radius: 4px;
    overflow: hidden;
}

.question-progress::-webkit-progress-bar {
    background-color: #e5e7eb;
}

.question-progress::-webkit-progress-value {
    background-color: #3b82f6;
}
</style>
