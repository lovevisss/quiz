<script setup lang="ts">
import Post from '@/components/Post.vue';
import UserInfo from '@/components/UserInfo.vue';
import { Button } from '@/components/ui/button';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps<{ id: string }>();
const user = ref<any>(null);
const userLoading = ref(true);
const posts = ref<any[]>([]);
const postLoading = ref(true);

onMounted(async () => {
    try {
        const userRes = await axios.get(`/api/users/${props.id}`);
        user.value =
            userRes.data.data?.attributes || userRes.data.data || userRes.data;
    } catch {
        user.value = null;
    } finally {
        userLoading.value = false;
    }
    try {
        const postsRes = await axios.get(`/api/users/${props.id}/posts`);
        posts.value = postsRes.data.data || [];
    } catch {
        posts.value = [];
    } finally {
        postLoading.value = false;
    }
});

function addFriend() {
    // TODO: Implement friend request logic
    alert('Friend request sent!');
}
</script>

<template>
    <div class="flex flex-row gap-8 p-8">
        <!-- Left: User photo/avatar -->
        <div class="flex w-1/4 flex-col items-center">
            <div v-if="userLoading">
                <div
                    class="h-32 w-32 animate-pulse rounded-full bg-gray-200"
                ></div>
            </div>
            <div v-else-if="user">
                <UserInfo :user="user" />
            </div>
            <div v-else>
                <div class="h-32 w-32 rounded-full bg-gray-100"></div>
            </div>
        </div>

        <!-- Right: User info and Add Friend button -->
        <div class="flex-1">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">
                        {{ user?.name || 'User' }}
                    </h2>
                    <p v-if="user?.email" class="text-gray-500">
                        {{ user.email }}
                    </p>
                </div>
                <Button @click="addFriend" variant="default">Add Friend</Button>
            </div>

            <div>
                <h3 class="mb-2 text-lg font-semibold">Posts</h3>
                <div v-if="postLoading">Loading posts...</div>
                <div v-else-if="posts.length === 0">
                    No posts found. Get started...
                </div>
                <div v-else>
                    <Post
                        v-for="post in posts"
                        :key="post.data.post_id"
                        :post="post"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
