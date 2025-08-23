<template>
  <div>
  <HeaderLayout />
  <!-- main content area -->
  <main class="container mx-auto p-2">
    <router-view></router-view>
  </main>
  <!-- <FooterLayout /> -->
  </div>
</template>

<script setup>
import HeaderLayout from "../components/common/HeaderLayout.vue";
import FooterLayout from "../components/common/FooterLayout.vue";
import { initFlowbite } from "flowbite";
import { onMounted } from "vue";  
onMounted(() => {
  initFlowbite();
});
</script>

<style lang="scss" scoped>

</style>