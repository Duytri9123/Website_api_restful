<template>
  <div>
    <div class="antialiased bg-gray-50 dark:bg-gray-900">
      <Navbar></Navbar>
      <Sidebar></Sidebar>
      <main class="p-4 md:ml-64 h-auto pt-10">
        <router-view></router-view>
      </main>
    </div>
  </div>
</template>

<script setup>
import Navbar from "../pages/admin/Navbar.vue";
import Sidebar from "../pages/admin/Sidebar.vue";
</script>

<style lang="scss" scoped></style>
