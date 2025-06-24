<template>
    <div class="flex flex-col min-h-screen">
        <HeroLayout/>
        <ProductList/>

        
    </div>
</template>

<script setup>
import DefaultLayout from '@/layout/DefaultLayout.vue';
import HeroLayout from '@/components/common/HeroLayout.vue';
import ProductList from './user/products/Productlist.vue';

</script>

<style lang="scss" scoped></style>
