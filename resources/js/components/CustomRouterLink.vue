<template>
  <RouterLink v-bind="$attrs" v-slot="{ href, navigate }">
    <a :href="href" @click="navigate" v-bind="$attrs">
      <slot />
    </a>
  </RouterLink>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>