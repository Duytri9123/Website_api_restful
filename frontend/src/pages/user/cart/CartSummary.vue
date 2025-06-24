<template>
  <div class="bg-gray-50 p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold text-gray-800 mb-4">Tóm tắt đơn hàng</h2>
    <div class="flex justify-between items-center mb-2">
      <p class="text-gray-700">Tổng phụ:</p>
      <p class="text-gray-900 font-medium">{{ formatCurrency(subtotal) }}</p>
    </div>
    <div class="flex justify-between items-center mb-2">
      <p class="text-gray-700">Phí vận chuyển:</p>
      <p class="text-gray-900 font-medium">{{ formatCurrency(shippingFee) }}</p>
    </div>
    <div v-if="discount > 0" class="flex justify-between items-center mb-2">
      <p class="text-green-600">Giảm giá:</p>
      <p class="text-green-600 font-medium">-{{ formatCurrency(discount) }}</p>
    </div>
    <div class="border-t border-gray-300 mt-4 pt-4 flex justify-between items-center">
      <p class="text-lg font-bold text-gray-800">Tổng cộng:</p>
      <p class="text-2xl font-extrabold text-blue-600">{{ formatCurrency(total) }}</p>
    </div>
    <button
      class="mt-6 w-full bg-blue-600 text-white py-3 rounded-md hover:bg-blue-700 transition duration-300 ease-in-out focus:outline-none focus:ring-4 focus:ring-blue-300"
      @click="checkout"
    >
      Tiến hành thanh toán
    </button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from 'vue';

const props = defineProps({
  cartItems: {
    type: Array,
    default: () => [],
  },
  shippingFee: {
    type: Number,
    default: 0,
  },
  discount: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['checkout']);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const subtotal = computed(() => {
  return props.cartItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
});

const total = computed(() => {
  return subtotal.value + props.shippingFee - props.discount;
});

const checkout = () => {
  emit('checkout');
};
</script>