<template>
    <div class="flex flex-col min-h-screen">
        <HeroLayout/>
        <!-- <ProductList/> -->
        <ProductAll/>
        <ProductMan/>

        
    </div>
</template>

<script setup>
import DefaultLayout from '@/layout/DefaultLayout.vue';
import HeroLayout from '@/components/common/HeroLayout.vue';
import ProductList from './user/products/Productlist.vue';
import ProductAll from './user/products/ProductAll.vue';
import ProductMan from './user/products/ProductMan.vue';

</script>

<style lang="scss" scoped></style>
