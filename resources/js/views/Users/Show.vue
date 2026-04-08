<script>
import axios from 'axios';

export default {
    name: 'UserShow',
    data() {
        return {
            user: null,
            userLoading: true,
            postLoading: true,
        };
    },
    mounted() {
        axios
            .get('api/users/' + this.$route.params.userId)
            .then((res) => {
                this.user = res.data;
            })
            .catch((error) => {
                console.log('Unable to fetch user' + error.name);
            })
            .finally(() => {
                this.userLoading = false;
            });
        axios
            .get('/api/posts' + this.$route.params.userId)
            .then((res) => {
                this.posts = res.data;
                this.postLoading = false;
            })
            .catch((error) => {
                console.log('Unable to fetch posts' + error.name);
                this.postLoading = false;
            });
    },
};
</script>

<template>
    <div></div>
</template>

<style scoped></style>
