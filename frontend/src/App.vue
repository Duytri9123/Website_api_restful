<script setup>
import { onMounted } from "vue";
import { useCartStore } from "./stores/cart.js";

const cartStore = useCartStore();

onMounted(() => {
  cartStore.loadFromLocalStorage();
});
</script>

<template>
  <RouterView></RouterView>
</template>

<style scoped></style>
