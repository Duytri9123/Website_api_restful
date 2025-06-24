<template>
  <div class="flex items-center py-4 border-b border-gray-200">
    <div class="w-20 h-20 mr-4 flex-shrink-0">
      <img :src="item.image" :alt="item.name" class="w-full h-full object-cover rounded-md" />
    </div>
    <div class="flex-grow">
      <h3 class="text-lg font-semibold text-gray-800">{{ item.name }}</h3>
      <p class="text-gray-600">Giá: {{ formatCurrency(item.price) }}</p>
      <div class="flex items-center mt-2">
        <button
          @click="decreaseQuantity"
          class="px-3 py-1 bg-gray-200 text-gray-700 rounded-l-md hover:bg-gray-300 transition"
          :disabled="item.quantity <= 1"
        >
          -
        </button>
        <input
          type="number"
          v-model.number="item.quantity"
          @change="updateQuantityManually"
          class="w-16 text-center border-t border-b border-gray-200 py-1 focus:outline-none focus:ring-2 focus:ring-blue-500"
          min="1"
        />
        <button
          @click="increaseQuantity"
          class="px-3 py-1 bg-gray-200 text-gray-700 rounded-r-md hover:bg-gray-300 transition"
        >
          +
        </button>
        <p class="ml-4 text-gray-700 font-medium">
          Tổng: {{ formatCurrency(item.price * item.quantity) }}
        </p>
      </div>
    </div>
    <button @click="removeItem" class="ml-4 text-red-500 hover:text-red-700 transition">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
        />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['update-quantity', 'remove-item']);

const formatCurrency = (value) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
};

const increaseQuantity = () => {
  emit('update-quantity', props.item.id, props.item.quantity + 1);
};

const decreaseQuantity = () => {
  if (props.item.quantity > 1) {
    emit('update-quantity', props.item.id, props.item.quantity - 1);
  }
};

const updateQuantityManually = (event) => {
  let newQuantity = parseInt(event.target.value);
  if (isNaN(newQuantity) || newQuantity < 1) {
    newQuantity = 1; // Đảm bảo số lượng không nhỏ hơn 1
  }
  emit('update-quantity', props.item.id, newQuantity);
};

const removeItem = () => {
  emit('remove-item', props.item.id);
};
</script>