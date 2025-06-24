<template>
    <slot></slot>
</template>

<script setup>
import { initFlowbite } from "flowbite";
import { onMounted } from "vue";
onMounted(() => {
  initFlowbite();
});
</script>

<style lang="scss" scoped></style>
