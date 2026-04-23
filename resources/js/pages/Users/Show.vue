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
const friendship = ref<any>(null);
const friendshipLoading = ref(true);

onMounted(async () => {
    try {
        const userRes = await axios.get(`/api/users/${props.id}`);
        user.value =
            userRes.data.data?.attributes || userRes.data.data || userRes.data;
        // Friendship info may be nested in user resource
        friendship.value =
            userRes.data.data?.attributes?.friendship?.data || null;
    } catch {
        user.value = null;
        friendship.value = null;
    } finally {
        userLoading.value = false;
        friendshipLoading.value = false;
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

async function addFriend() {
    try {
        const res = await axios.post('/api/friends', { friend_id: props.id });
        // Update friendship state after sending request
        friendship.value = res.data.data;
        alert('Friend request sent!');
    } catch (e: any) {
        alert(e?.response?.data?.errors?.detail || 'Failed to send request');
    }
}

async function acceptFriendRequest() {
    try {
        const res = await axios.post(`/api/friends/${props.id}/accept`);
        friendship.value = res.data.data;
        alert('Friend request accepted!');
    } catch (e: any) {
        alert(e?.response?.data?.errors?.detail || 'Failed to accept request');
    }
}

function friendshipButtonLabel() {
    if (friendshipLoading.value) return '...';
    if (!friendship.value) return 'Add Friend';
    if (friendship.value.attributes?.confirmed_at) return 'Friends';
    // If the current user is the recipient, show accept
    if (
        friendship.value.attributes?.pending &&
        friendship.value.attributes?.can_accept
    )
        return 'Accept Request';
    if (friendship.value.attributes?.pending) return 'Request Sent';
    return 'Add Friend';
}

function canAddFriend() {
    return !friendship.value;
}

function canAcceptFriend() {
    return (
        friendship.value &&
        friendship.value.attributes?.pending &&
        friendship.value.attributes?.can_accept
    );
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
                <Button
                    v-if="canAddFriend()"
                    @click="addFriend"
                    variant="default"
                >
                    {{ friendshipButtonLabel() }}
                </Button>
                <Button
                    v-else-if="canAcceptFriend()"
                    @click="acceptFriendRequest"
                    variant="default"
                >
                    {{ friendshipButtonLabel() }}
                </Button>
                <Button v-else variant="secondary" disabled>
                    {{ friendshipButtonLabel() }}
                </Button>
                <button
                    v-if="
                        friendshipButtonLabel() &&
                        friendshipButtonLabel() === 'Accept Request'
                    "
                    class="rounded bg-gray-400 px-3 py-1"
                    @click="
                        $store.dispatch(
                            'sendFriendRequest',
                            $router.params.userId,
                        )
                    "
                >
                    Accept
                </button>
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
