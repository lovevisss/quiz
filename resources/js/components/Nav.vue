<script setup lang="ts">
import { RouterLink } from 'vue-router';
</script>

<template>
    <div class="bg-white h-12 flex items-center px-4 border-b border-b-gray-200 shadow-sm">
        <div class="w-1/3 flex items-center gap-3">
            <RouterLink to="/index" class="text-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                    <path d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2ZM13.5 12H14.5V9H13.5V12ZM13.5 13.5H14.5V15H13.5V13.5ZM11.5 9H10.5V12H11.5V9ZM11.5 13.5H10.5V15H11.5V13.5ZM9.5 9H8.5V12H9.5V9ZM9.5 13.5H8.5V15H9.5V13.5ZM15.5 9H14.5V12H15.5V9ZM15.5 13.5H14.5V15H15.5V13.5Z" />
                </svg>
            </RouterLink>

            <input type="text" name="search" class="w-48 rounded-full bg-gray-200 focus:outline-none text-sm pl-8" placeholder="search" />
        </div>

        <div class="w-1/3 flex items-center justify-center gap-6 text-sm font-medium">
            <RouterLink to="/index" class="text-slate-700 hover:text-blue-600" active-class="text-blue-600">Home</RouterLink>
            <RouterLink to="/friends" class="text-slate-700 hover:text-blue-600" active-class="text-blue-600">Friends</RouterLink>
            <RouterLink to="/watch" class="text-slate-700 hover:text-blue-600" active-class="text-blue-600">Watch</RouterLink>
        </div>

        <div class="w-1/3 flex justify-end"></div>
    </div>
</template>

<style scoped></style>
