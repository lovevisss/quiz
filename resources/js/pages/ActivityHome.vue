<template>
    <div
        data-testid="activity-home"
        class="flex flex-col items-center space-y-4 p-4"
    >
        <div class="text-center">
            <h1 class="text-xl font-bold">Welcome to the Activity</h1>
            <p class="text-sm text-gray-600">Get ready to start your quiz!</p>
        </div>
        <div class="fixed-footer">
            <button
                class="rounded-lg bg-blue-500 px-6 py-3 text-white shadow-md"
            >
                Start Quiz
            </button>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'ActivityHome',
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
    justify-content: center;
}
</style>
