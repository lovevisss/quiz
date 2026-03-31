<script setup lang="ts">
import NewPost from '@/components/NewPost.vue';
import axios from 'axios';
import { onMounted, ref } from 'vue';

type FeedUser = {
    id: number;
    name: string;
};

type FeedPost = {
    id: number;
    body: string;
    image_url: string | null;
    created_at: string;
    user: FeedUser;
};

const posts = ref<FeedPost[]>([]);
const isLoading = ref(true);
const errorMessage = ref('');

async function fetchPosts() {
    isLoading.value = true;
    errorMessage.value = '';

    try {
        const response = await axios.get<FeedPost[]>('/api/posts');
        posts.value = response.data;
    } catch (error) {
        console.error(error);
        errorMessage.value = 'Failed to load posts. Please refresh and try again.';
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchPosts);
</script>

<template>
    <div class="flex flex-col items-center py-4 overflow-y-auto">
        <h1 class="mb-4 text-3xl font-bold">News Feed</h1>
        <p class="mb-4 text-gray-600">Latest posts from database</p>
        <NewPost />

        <p v-if="isLoading" class="mt-6 text-sm text-slate-500">Loading posts...</p>
        <p v-else-if="errorMessage" class="mt-6 text-sm text-red-500">{{ errorMessage }}</p>
        <p v-else-if="posts.length === 0" class="mt-6 text-sm text-slate-500">No posts found.</p>

        <div v-else class="mt-6 w-2/3 space-y-4">
            <article v-for="post in posts" :key="post.id" class="rounded bg-white p-4 shadow">
                <div class="mb-2 text-sm font-semibold">{{ post.user.name }}</div>
                <p class="text-sm text-slate-500">{{ new Date(post.created_at).toLocaleString() }}</p>
                <p class="mt-3 text-slate-800">{{ post.body }}</p>
                <img v-if="post.image_url" :src="post.image_url" alt="post image" class="mt-4 w-full rounded" />
            </article>
        </div>
    </div>
</template>

<style scoped></style>
