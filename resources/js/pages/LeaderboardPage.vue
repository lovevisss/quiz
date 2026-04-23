<template>
    <div data-testid="leaderboard-page" class="space-y-4 p-4">
        <h1 class="text-2xl font-bold">Leaderboard</h1>
        <ul class="space-y-2">
            <li class="leaderboard-row">
                <span>1. Alice</span>
                <span>100 points</span>
            </li>
            <li class="leaderboard-row">
                <span>2. Bob</span>
                <span>90 points</span>
            </li>
        </ul>
        <div class="fixed-footer">
            <button class="rounded-lg bg-blue-500 px-6 py-3 text-white">
                Retry
            </button>
        </div>
    </div>
</template>

<script lang="ts">
export default {
    name: 'LeaderboardPage',
};
</script>

<style scoped>
.leaderboard-row {
    padding: 0.5rem 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

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
