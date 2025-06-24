<template>
  <div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Giỏ hàng của bạn</h1>

    <div v-if="cart.length === 0" class="text-center py-12 bg-white rounded-lg shadow-md">
      <p class="text-xl text-gray-600 mb-4">Giỏ hàng của bạn đang trống.</p>
      <router-link
        to="/"
        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5 mr-2"
          viewBox="0 0 20 20"
          fill="currentColor"
        >
          <path
            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
          />
        </svg>
        Tiếp tục mua sắm
      </router-link>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <div class="divide-y divide-gray-200">
          <CartItem
            v-for="item in cart"
            :key="item.id"
            :item="item"
            @update-quantity="updateItemQuantity"
            @remove-item="removeItemFromCart"
          />
        </div>
      </div>

      <div class="lg:col-span-1">
        <CartSummary :cartItems="cart" :shippingFee="shipping" :discount="appliedDiscount" @checkout="handleCheckout" />
        <div class="mt-6 bg-white p-6 rounded-lg shadow-md">
          <h3 class="text-lg font-semibold text-gray-800 mb-3">Mã giảm giá</h3>
          <div class="flex">
            <input
              type="text"
              v-model="discountCode"
              placeholder="Nhập mã giảm giá"
              class="flex-grow border border-gray-300 rounded-l-md px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button
              @click="applyDiscount"
              class="bg-green-500 text-white px-5 py-2 rounded-r-md hover:bg-green-600 transition duration-300 ease-in-out"
            >
              Áp dụng
            </button>
          </div>
          <p v-if="discountMessage" :class="discountMessage.type === 'success' ? 'text-green-600' : 'text-red-600'" class="mt-2 text-sm">{{ discountMessage.text }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import CartItem from './CartItem.vue';
import CartSummary from './CartSummary.vue';

// Dữ liệu giỏ hàng mẫu (có thể lấy từ store hoặc API thực tế)
const cart = ref([
  { id: 1, name: 'Áo thun cơ bản', price: 250000, quantity: 2, image: 'https://via.placeholder.com/100/FF5733/FFFFFF?text=Ao' },
  { id: 2, name: 'Quần Jeans Xanh', price: 500000, quantity: 1, image: 'https://via.placeholder.com/100/3366FF/FFFFFF?text=Quan' },
  { id: 3, name: 'Giày thể thao', price: 800000, quantity: 1, image: 'https://via.placeholder.com/100/33FF57/FFFFFF?text=Giay' },
]);

const shipping = ref(30000); // Phí vận chuyển
const discountCode = ref('');
const appliedDiscount = ref(0);
const discountMessage = ref(null); // { text: '...', type: 'success' | 'error' }

// Cập nhật số lượng sản phẩm
const updateItemQuantity = (id, newQuantity) => {
  const itemIndex = cart.value.findIndex(item => item.id === id);
  if (itemIndex !== -1) {
    cart.value[itemIndex].quantity = newQuantity;
  }
};

// Xóa sản phẩm khỏi giỏ hàng
const removeItemFromCart = (id) => {
  cart.value = cart.value.filter(item => item.id !== id);
};

// Xử lý áp dụng mã giảm giá
const applyDiscount = () => {
  if (discountCode.value === 'SUMMER20') {
    appliedDiscount.value = 50000; // Giảm 50.000 VND
    discountMessage.value = { text: 'Áp dụng mã giảm giá thành công!', type: 'success' };
  } else if (discountCode.value === 'FREESHIP') {
    shipping.value = 0; // Miễn phí vận chuyển
    appliedDiscount.value = 0; // Đảm bảo không có giảm giá tiền mặt nếu là mã freeship
    discountMessage.value = { text: 'Miễn phí vận chuyển đã được áp dụng!', type: 'success' };
  }
  else {
    appliedDiscount.value = 0;
    discountMessage.value = { text: 'Mã giảm giá không hợp lệ.', type: 'error' };
  }
};

// Xử lý khi người dùng nhấn nút thanh toán
const handleCheckout = () => {
  alert('Chức năng thanh toán sẽ được phát triển tại đây!');
  // Chuyển hướng đến trang thanh toán hoặc gọi API tạo đơn hàng
};
</script>

<style scoped>
/* Có thể thêm các style tùy chỉnh nếu cần, nhưng Tailwind CSS đã đủ mạnh mẽ */
</style>