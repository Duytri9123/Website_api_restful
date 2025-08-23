<script setup>
import { ref, computed, onMounted, provide } from "vue";
import CartItem from "./CartItem.vue";
import { useCartStore } from "../../../stores/cart.js";
import { storeToRefs } from "pinia";

const cartStore = useCartStore();
const { cartItems } = storeToRefs(cartStore);

const selectedItems = ref(new Set());
const selectAll = ref(false);
const shipping = ref(30000);
const discountCode = ref("");
const appliedDiscount = ref(0);
const discountMessage = ref(null);
const showPaymentDetail = ref(false);

// Gom nhóm theo sản phẩm (giống logic ban đầu nhưng theo chuẩn Shopee)
const groupedCart = computed(() => {
  const groups = {};
  cartItems.value.forEach((item) => {
    const productId = item.productId || item.product_id;
    if (!groups[productId]) {
      groups[productId] = {
        productId: productId,
        productName: item.name,
        items: [],
        selected: false,
      };
    }
    groups[productId].items.push({
      ...item,
      variantId: item.variant?.id,
    });
  });

  // Cập nhật trạng thái selected cho từng group
  Object.keys(groups).forEach((productId) => {
    const group = groups[productId];
    const selectedCount = group.items.filter((item) =>
      selectedItems.value.has(item.variantId)
    ).length;
    group.selected = selectedCount === group.items.length && group.items.length > 0;
    group.partialSelected = selectedCount > 0 && selectedCount < group.items.length;
  });

  return groups;
});

// Tính toán các items được chọn
const selectedCartItems = computed(() => {
  return cartItems.value.filter((item) => selectedItems.value.has(item.variant?.id));
});

const selectedItemsCount = computed(() => {
  return selectedItems.value.size;
});

const totalSelectedPrice = computed(() => {
  return selectedCartItems.value.reduce((total, item) => {
    return total + (item.variant?.selling_price || 0) * item.quantity;
  }, 0);
});

const totalOriginalPrice = computed(() => {
  return selectedCartItems.value.reduce((total, item) => {
    return (
      total +
      (item.variant?.original_price || item.variant?.selling_price || 0) * item.quantity
    );
  }, 0);
});

const totalSavings = computed(() => {
  return totalOriginalPrice.value - totalSelectedPrice.value;
});

const finalTotal = computed(() => {
  if (totalSelectedPrice.value == 0) {
    return 0;
  } else {
  }
  return totalSelectedPrice.value + shipping.value - appliedDiscount.value;
});

// Xử lý chọn tất cả
const handleSelectAll = () => {
  if (selectAll.value) {
    // Bỏ chọn tất cả
    selectedItems.value.clear();
    selectAll.value = false;
  } else {
    // Chọn tất cả
    cartItems.value.forEach((item) => {
      selectedItems.value.add(item.variant?.id);
    });
    selectAll.value = true;
  }
};

// Xử lý chọn/bỏ chọn sản phẩm
const handleToggleProduct = (productId) => {
  const group = groupedCart.value[productId];
  if (!group) return;

  if (group.selected) {
    // Bỏ chọn tất cả items trong group
    group.items.forEach((item) => {
      selectedItems.value.delete(item.variantId);
    });
  } else {
    // Chọn tất cả items trong group
    group.items.forEach((item) => {
      selectedItems.value.add(item.variantId);
    });
  }

  updateSelectAllState();
};

// Xử lý chọn/bỏ chọn item
const handleToggleItem = (variantId) => {
  if (selectedItems.value.has(variantId)) {
    selectedItems.value.delete(variantId);
  } else {
    selectedItems.value.add(variantId);
  }

  updateSelectAllState();
};

// Cập nhật trạng thái chọn tất cả
const updateSelectAllState = () => {
  const totalItems = cartItems.value.length;
  const selectedCount = selectedItems.value.size;

  selectAll.value = selectedCount === totalItems && totalItems > 0;
};

// Xử lý số lượng
const updateItemQuantity = (variantId, newQuantity) => {
  cartStore.updateQuantity(variantId, newQuantity);
};

const removeItemFromCart = (variantId) => {
  selectedItems.value.delete(variantId);
  cartStore.removeFromCart(variantId);
  updateSelectAllState();
};

const updateVariant = (variantId, key, value) => {
  cartStore.updateVariant(variantId, key, value);
};

// Xử lý thay đổi variant
const handleChangeVariant = (oldVariantId, newVariant) => {
  // Tìm item trong cart
  const itemIndex = cartItems.value.findIndex(
    (item) => item.variant?.id === oldVariantId
  );
  if (itemIndex !== -1) {
    // Cập nhật variant mới cho item
    cartStore.updateVariant(oldVariantId, newVariant);

    // Cập nhật selected items nếu cần
    if (selectedItems.value.has(oldVariantId)) {
      selectedItems.value.delete(oldVariantId);
      selectedItems.value.add(newVariant.id);
    }
  }
};

// Xử lý xóa items được chọn
const removeSelectedItems = () => {
  if (selectedItems.value.size === 0) {
    alert("Vui lòng chọn sản phẩm cần xóa");
    return;
  }

  if (confirm(`Bạn có chắc muốn xóa ${selectedItems.value.size} sản phẩm đã chọn?`)) {
    selectedItems.value.forEach((variantId) => {
      cartStore.removeFromCart(variantId);
    });
    selectedItems.value.clear();
    updateSelectAllState();
  }
};

// Xử lý mã giảm giá
const applyDiscount = () => {
  if (discountCode.value === "SUMMER20") {
    appliedDiscount.value = 50000;
    discountMessage.value = { text: "Áp dụng mã giảm giá thành công!", type: "success" };
  } else if (discountCode.value === "FREESHIP") {
    shipping.value = 0;
    appliedDiscount.value = 0;
    discountMessage.value = {
      text: "Miễn phí vận chuyển đã được áp dụng!",
      type: "success",
    };
  } else {
    appliedDiscount.value = 0;
    discountMessage.value = { text: "Mã giảm giá không hợp lệ.", type: "error" };
  }

  // Tự động xóa message sau 3 giây
  setTimeout(() => {
    discountMessage.value = null;
  }, 3000);
};

// Xử lý thanh toán
const handleCheckout = () => {
  if (selectedItems.value.size === 0) {
    alert("Vui lòng chọn sản phẩm để thanh toán");
    return;
  }

  // Logic thanh toán - có thể chuyển đến trang checkout với selected items
  const checkoutData = {
    items: selectedCartItems.value,
    total: finalTotal.value,
    shipping: shipping.value,
    discount: appliedDiscount.value,
  };

  console.log("Checkout data:", checkoutData);
  alert(
    `Thanh toán ${
      selectedItems.value.size
    } sản phẩm với tổng tiền ${finalTotal.value.toLocaleString()}đ`
  );
};

onMounted(() => {
  cartStore.loadFromLocalStorage();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="container mx-auto max-w-7xl px-4 py-6">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm mb-4 p-4">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
          <svg width="28" height="28" viewBox="0 0 16 16">
            <path
              fill="currentColor"
              d="M14 13.1V12H4.6l.6-1.1l9.2-.9L16 4H3.7L3 1H0v1h2.2l2.1 8.4L3 13v1.5c0 .8.7 1.5 1.5 1.5S6 15.3 6 14.5S5.3 13 4.5 13H12v1.5c0 .8.7 1.5 1.5 1.5s1.5-.7 1.5-1.5c0-.7-.4-1.2-1-1.4zM4 5h10.7l-1.1 4l-8.4.9L4 5z"
            />
          </svg>
          Giỏ hàng
        </h1>
      </div>

      <!-- Giỏ hàng trống -->
      <div
        v-if="!cartItems || cartItems.length === 0"
        class="bg-white rounded-lg shadow-sm text-center py-16"
      >
        <div class="mb-4">
          <svg
            class="w-20 h-20 mx-auto text-gray-300"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5-6m15 0L19 7"
            />
          </svg>
        </div>
        <p class="text-xl text-gray-400 mb-6">Giỏ hàng của bạn đang trống</p>
        <router-link
          to="/"
          class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-lg shadow-sm text-white bg-orange-500 hover:bg-orange-600 transition-colors"
        >
          Mua ngay
        </router-link>
      </div>

      <!-- Nội dung giỏ hàng -->
      <div v-else>
        <!-- Header table -->
        <div class="bg-white rounded-lg shadow-sm mb-4">
          <div class="p-4 border-b border-gray-200">
            <div class="flex items-center">
              <div class="flex items-center mr-6">
                <input
                  type="checkbox"
                  :checked="selectAll"
                  @change="handleSelectAll"
                  class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 mr-2"
                />
                <span class="text-sm">Chọn tất cả ({{ cartItems.length }} sản phẩm)</span>
              </div>

              <button
                @click="removeSelectedItems"
                :disabled="selectedItems.size === 0"
                class="text-sm text-gray-400 hover:text-red-500 disabled:cursor-not-allowed disabled:hover:text-gray-400"
              >
                Xóa
              </button>
            </div>
          </div>

          <!-- Danh sách sản phẩm -->
          <div class="divide-y divide-gray-100">
            <div v-for="(group, productId) in groupedCart" :key="productId" class="p-4">
              <!-- Header sản phẩm -->
              <div class="flex items-center mb-3">
                <input
                  type="checkbox"
                  :checked="group.selected"
                  :indeterminate="group.partialSelected"
                  @change="handleToggleProduct(productId)"
                  class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 mr-3"
                />
                <div class="flex items-center">
                  <svg
                    class="w-6 h-6 text-yellow-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path
                      d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.176 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                    />
                  </svg>
                  <span class="text-sm font-medium text-gray-900">{{
                    group.productName
                  }}</span>
                </div>
              </div>

              <!-- Items trong group -->
              <div class="ml-7 space-y-0 divide-y divide-gray-50">
                <CartItem
                  v-for="item in group.items"
                  :key="item.variantId"
                  :item="item"
                  :selected="selectedItems.has(item.variantId)"
                  :cart-items="cartItems"
                  @update-quantity="updateItemQuantity"
                  @remove-item="removeItemFromCart"
                  @update-variant="updateVariant"
                  @toggle-select="handleToggleItem"
                  @change-variant="handleChangeVariant"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Mã giảm giá -->
        <div class="bg-white rounded-lg shadow-sm mb-4 p-4">
          <h3 class="text-lg font-medium text-gray-900 mb-3 flex items-center">
            <svg
              class="w-5 h-5 mr-2 text-orange-500"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
              />
            </svg>
            Shop Voucher
          </h3>
          <div class="flex max-w-md">
            <input
              type="text"
              v-model="discountCode"
              placeholder="Nhập mã Voucher"
              class="flex-grow border border-gray-300 rounded-l-lg px-4 py-2 text-sm focus:outline-none focus:border focus:border-orange-500"
            />
            <button
              @click="applyDiscount"
              class="bg-orange-500 text-white px-6 py-3 rounded-r-lg hover:bg-orange-600 transition-colors text-sm font-medium"
            >
              ÁP DỤNG
            </button>
          </div>
          <div
            v-if="discountMessage"
            :class="[
              'mt-2 text-sm p-2 rounded',
              discountMessage.type === 'success'
                ? 'bg-green-50 text-green-600 border border-green-200'
                : 'bg-red-50 text-red-600 border border-red-200',
            ]"
          >
            {{ discountMessage.text }}
          </div>
        </div>

        <!-- Thanh toán sticky -->
        <div
          class="sticky bottom-0 bg-white border-t border-gray-200 shadow-lg rounded-t-lg"
        >
          <div class="p-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <input
                  type="checkbox"
                  :checked="selectAll"
                  @change="handleSelectAll"
                  class="w-4 h-4 text-orange-500 border-gray-300 rounded focus:ring-orange-500 focus:ring-2 mr-3"
                />
                <span class="text-sm">
                  Chọn tất cả
                  <span class="text-gray-500">({{ cartItems.length }})</span>
                </span>

                <button
                  @click="removeSelectedItems"
                  :disabled="selectedItems.size === 0"
                  class="ml-6 text-sm text-gray-400 hover:text-red-500 disabled:cursor-not-allowed disabled:hover:text-gray-400"
                >
                  Xóa
                </button>
              </div>

              <div class="flex items-center">
                <div class="text-right mr-6 relative">
                  <div class="text-sm text-gray-500 flex items-center">
                    Tổng thanh toán ({{ selectedItemsCount }} sản phẩm):
                    <button
                      @click="showPaymentDetail = !showPaymentDetail"
                      class="ml-2 text-blue-500 hover:text-blue-600 focus:outline-none"
                    >
                      <svg
                        :class="[
                          'w-4 h-4 transition-transform',
                          showPaymentDetail ? 'rotate-180' : '',
                        ]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M19 9l-7 7-7-7"
                        />
                      </svg>
                    </button>
                  </div>

                  <!-- Chi tiết thanh toán (dropdown) -->
                  <div
                    v-if="showPaymentDetail"
                    class="absolute bottom-full right-0 mb-2 bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-80 z-10"
                  >
                    <div class="space-y-2">
                      <!-- Tổng tiền sản phẩm -->
                      <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Tổng tiền hàng:</span>
                        <div class="flex items-center">
                          <span
                            v-if="totalSavings > 0"
                            class="text-gray-400 line-through mr-2"
                          >
                            ₫{{ totalOriginalPrice.toLocaleString() }}
                          </span>
                          <span class="font-medium"
                            >₫{{ totalSelectedPrice.toLocaleString() }}</span
                          >
                        </div>
                      </div>

                      <!-- Phí vận chuyển -->
                      <div class="flex justify-between items-center text-sm">
                        <span class="text-gray-600">Phí vận chuyển:</span>

                        <span class="font-medium" v-if="totalOriginalPrice > 0"
                          >₫{{ shipping.toLocaleString() }}</span
                        >
                        <span class="font-medium" v-else
                          >₫{{ shipping.toLocaleString() }}</span
                        >
                      </div>

                      <!-- Giảm giá voucher -->
                      <div
                        v-if="appliedDiscount > 0"
                        class="flex justify-between items-center text-sm"
                      >
                        <span class="text-gray-600">Voucher giảm giá:</span>
                        <span class="font-medium text-green-600"
                          >-₫{{ appliedDiscount.toLocaleString() }}</span
                        >
                      </div>

                      <!-- Tiết kiệm từ giảm giá sản phẩm -->
                      <div
                        v-if="totalSavings > 0"
                        class="flex justify-between items-center text-sm"
                      >
                        <span class="text-gray-600">Tiết kiệm:</span>
                        <span class="font-medium text-green-600"
                          >-₫{{ totalSavings.toLocaleString() }}</span
                        >
                      </div>

                      <!-- Đường kẻ ngăn cách -->
                      <hr class="border-gray-200 my-2" />

                      <!-- Tổng cuối cùng -->
                      <div class="flex justify-between items-center font-semibold">
                        <span class="text-gray-900">Tổng thanh toán:</span>
                        <span class="text-orange-500"
                          >₫{{ finalTotal.toLocaleString() }}</span
                        >
                      </div>
                    </div>
                  </div>

                  <div class="flex items-center">
                    <span
                      v-if="totalSavings > 0"
                      class="text-sm text-gray-400 line-through mr-2"
                    >
                      ₫{{ totalOriginalPrice.toLocaleString() }}
                    </span>
                    <span class="text-xl font-bold text-orange-500">
                      ₫{{ finalTotal.toLocaleString() }}
                    </span>
                  </div>
                  <div v-if="totalSavings > 0" class="text-sm text-green-600">
                    Tiết kiệm ₫{{ totalSavings.toLocaleString() }}
                  </div>
                </div>

                <button
                  @click="handleCheckout"
                  :disabled="selectedItems.size === 0"
                  class="bg-orange-500 text-white px-8 py-3 rounded-lg font-medium hover:bg-orange-600 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors text-sm"
                >
                  Mua hàng
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
