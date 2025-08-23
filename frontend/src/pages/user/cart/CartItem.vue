<script setup>
import { ref, computed, inject } from "vue";
import axiosClient from "../../../axiosClient.js";

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  selected: {
    type: Boolean,
    default: false,
  },
  cartItems: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits([
  "update-quantity",
  "remove-item",
  "update-variant",
  "toggle-select",
  "change-variant",
]);

const isEditing = ref(false);
const tempQuantity = ref(props.item.quantity);

// Tính giá cho item
const itemPrice = computed(() => {
  return props.item.variant?.selling_price || 0;
});

const itemOriginalPrice = computed(() => {
  return props.item.variant?.original_price || 0;
});

const totalPrice = computed(() => {
  return itemPrice.value * props.item.quantity;
});

const hasDiscount = computed(() => {
  return itemOriginalPrice.value > itemPrice.value;
});

const discountPercent = computed(() => {
  if (!hasDiscount.value) return 0;
  return Math.round(
    ((itemOriginalPrice.value - itemPrice.value) / itemOriginalPrice.value) * 100
  );
});

// Lấy ảnh sản phẩm
const getImageUrl = (path) =>
  path ? `${axiosClient.defaults.baseURL}/storage/${path}` : "";

const productImage = computed(() => {
  const images = props.item.variant?.images || [];
  if (images.length > 0) {
    const firstImage = images[0];
    return getImageUrl(firstImage.url || firstImage.image);
  }
  return "/default-product.png"; // fallback
});

// Xử lý số lượng
const decreaseQuantity = () => {
  if (props.item.quantity > 1) {
    emit("update-quantity", props.item.variant.id, props.item.quantity - 1);
  }
};

const increaseQuantity = () => {
  const maxQuantity = props.item.variant?.quantity || 999;
  if (props.item.quantity < maxQuantity) {
    emit("update-quantity", props.item.variant.id, props.item.quantity + 1);
  }
};

const startEdit = () => {
  isEditing.value = true;
  tempQuantity.value = props.item.quantity;
};

const confirmEdit = () => {
  let qty = parseInt(tempQuantity.value);
  const maxQuantity = props.item.variant?.quantity || 999;

  if (isNaN(qty) || qty < 1) qty = 1;
  if (qty > maxQuantity) qty = maxQuantity;

  emit("update-quantity", props.item.variant.id, qty);
  isEditing.value = false;
};

const cancelEdit = () => {
  isEditing.value = false;
  tempQuantity.value = props.item.quantity;
};

const removeItem = () => {
  emit("remove-item", props.item.variant.id);
};

const toggleSelect = () => {
  emit("toggle-select", props.item.variant.id);
};

// Hiển thị attributes của variant
const variantAttributes = computed(() => {
  if (!props.item.variant?.attribute_values) return [];
  return props.item.variant.attribute_values.map((attr) => ({
    name: attr.attribute.name,
    value: attr.value,
    code: attr.code,
  }));
});

// Modal và variant selection
const showAttributeModal = ref(false);
const selectedTempAttributes = ref({});
const availableVariants = ref([]);
const isLoadingVariants = ref(false);

// Kiểm tra variant có trùng với item khác trong giỏ hàng không
const isVariantInCart = (variant) => {
  if (!variant || !variant.id) return false;

  return props.cartItems.some((cartItem) => {
    // Bỏ qua chính item hiện tại
    if (cartItem.variant?.id === props.item.variant?.id) {
      return false;
    }

    // Kiểm tra ID variant
    return cartItem.variant?.id === variant.id;
  });
};

// Kiểm tra có attributes trùng với item khác trong giỏ hàng không
const hasMatchingAttributesInCart = (variant) => {
  if (!variant || !variant.attribute_values) return false;

  return props.cartItems.some((cartItem) => {
    // Bỏ qua chính item hiện tại
    if (cartItem.variant?.id === props.item.variant?.id) {
      return false;
    }

    // Kiểm tra cùng sản phẩm
    const isSameProduct =
      cartItem.productId === props.item.productId ||
      cartItem.product_id === props.item.productId;

    if (!isSameProduct || !cartItem.variant?.attribute_values) {
      return false;
    }

    // So sánh tất cả attribute values
    const cartItemAttrs = cartItem.variant.attribute_values;
    const variantAttrs = variant.attribute_values;

    // Nếu số lượng attribute khác nhau thì không trùng
    if (cartItemAttrs.length !== variantAttrs.length) {
      return false;
    }

    // Kiểm tra từng attribute có giống nhau không
    return cartItemAttrs.every((cartAttr) => {
      return variantAttrs.some(
        (variantAttr) =>
          cartAttr.attribute.name === variantAttr.attribute.name &&
          cartAttr.value === variantAttr.value
      );
    });
  });
};

// Khởi tạo selectedTempAttributes khi mở modal
const initTempAttributes = () => {
  selectedTempAttributes.value = {};
  if (props.item.variant?.attribute_values) {
    props.item.variant.attribute_values.forEach((attr) => {
      selectedTempAttributes.value[attr.attribute.name] = attr.value;
    });
  }
};

// Lấy tất cả variants của sản phẩm từ API
const fetchProductVariants = async () => {
  if (isLoadingVariants.value) return;

  try {
    isLoadingVariants.value = true;
    const response = await axiosClient.get(`api/products/${props.item.productId}`);

    if (response.data && response.data.data && response.data.data.variants) {
      availableVariants.value = response.data.data.variants;
    } else {
      availableVariants.value = [];
    }
  } catch (error) {
    console.error("Error fetching product variants:", error);
    availableVariants.value = [];
  } finally {
    isLoadingVariants.value = false;
  }
};

// Mở modal thay đổi phân loại
const openAttributeModal = async () => {
  initTempAttributes();
  await fetchProductVariants();
  showAttributeModal.value = true;
};

// Lấy tất cả attributes duy nhất từ variants
const uniqueAttributes = computed(() => {
  const attributeMap = new Map();

  availableVariants.value.forEach((variant) => {
    if (variant.attribute_values) {
      variant.attribute_values.forEach((attr) => {
        if (!attributeMap.has(attr.attribute.name)) {
          attributeMap.set(attr.attribute.name, {
            name: attr.attribute.name,
            id: attr.attribute.id,
          });
        }
      });
    }
  });

  return Array.from(attributeMap.values());
});

// Lấy các tùy chọn có sẵn cho một attribute
const getAvailableOptions = (attributeName) => {
  const optionMap = new Map();

  // Lọc các variant có cùng attributes khác (trừ attribute đang chọn)
  availableVariants.value.forEach((variant) => {
    if (!variant.attribute_values) return;

    const hasMatchingOtherAttrs = Object.keys(selectedTempAttributes.value).every(
      (key) => {
        if (key === attributeName) return true;
        return variant.attribute_values.some(
          (attr) =>
            attr.attribute.name === key &&
            attr.value === selectedTempAttributes.value[key]
        );
      }
    );

    if (hasMatchingOtherAttrs) {
      const attr = variant.attribute_values.find(
        (a) => a.attribute.name === attributeName
      );
      if (attr && !optionMap.has(attr.value)) {
        const isInCart = hasMatchingAttributesInCart(variant);
        const isCurrentVariant = variant.id === props.item.variant?.id;

        optionMap.set(attr.value, {
          value: attr.value,
          code: attr.code,
          available: variant.quantity > 0 && !isInCart,
          inCart: isInCart && !isCurrentVariant,
          isCurrentVariant: isCurrentVariant,
          price: parseFloat(variant.selling_price),
          originalPrice: parseFloat(variant.original_price),
          variant: variant,
        });
      }
    }
  });

  return Array.from(optionMap.values()).sort((a, b) => {
    // Sắp xếp: variant hiện tại đầu tiên, có sẵn tiếp theo, trong giỏ hàng cuối
    if (a.isCurrentVariant && !b.isCurrentVariant) return -1;
    if (!a.isCurrentVariant && b.isCurrentVariant) return 1;
    if (a.available && !b.available) return -1;
    if (!a.available && b.available) return 1;
    if (a.inCart && !b.inCart) return 1;
    if (!a.inCart && b.inCart) return -1;
    return 0;
  });
};

// Chọn tùy chọn attribute
const selectAttributeOption = (attributeName, value, option) => {
  // Không cho chọn nếu variant đã có trong giỏ hàng
  if (option.inCart) {
    return;
  }

  selectedTempAttributes.value[attributeName] = value;
};

// Tìm variant dựa trên attributes đã chọn
const findSelectedVariant = computed(() => {
  return availableVariants.value.find((variant) => {
    if (!variant.attribute_values) return false;

    return Object.keys(selectedTempAttributes.value).every((attrName) => {
      return variant.attribute_values.some(
        (attr) =>
          attr.attribute.name === attrName &&
          attr.value === selectedTempAttributes.value[attrName]
      );
    });
  });
});

// Kiểm tra có thể xác nhận thay đổi không
const canConfirmChange = computed(() => {
  const requiredAttrs = uniqueAttributes.value.map((attr) => attr.name);
  const hasAllRequired = requiredAttrs.every(
    (attr) => selectedTempAttributes.value[attr]
  );
  const selectedVariant = findSelectedVariant.value;

  if (!hasAllRequired || !selectedVariant) {
    return false;
  }

  // Không cho phép chọn nếu variant giống với hiện tại
  if (selectedVariant.id === props.item.variant.id) {
    return false;
  }

  // Không cho phép chọn nếu variant đã có trong giỏ hàng
  if (hasMatchingAttributesInCart(selectedVariant)) {
    return false;
  }

  return true;
});

// Xác nhận thay đổi attribute
const confirmAttributeChange = () => {
  const newVariant = findSelectedVariant.value;

  if (newVariant && canConfirmChange.value) {
    emit("change-variant", props.item.variant.id, newVariant);
    showAttributeModal.value = false;
  }
};

// Đóng modal
const closeAttributeModal = () => {
  showAttributeModal.value = false;
  selectedTempAttributes.value = {};
  availableVariants.value = [];
};
</script>

<template>
  <div
    class="flex items-start py-4 border-b border-gray-100 hover:bg-gray-50 transition-colors"
  >
    <!-- Checkbox chọn sản phẩm -->
    <div class="flex items-center mr-4">
      <input
        type="checkbox"
        :checked="selected"
        @change="toggleSelect"
        class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
      />
    </div>

    <!-- Ảnh sản phẩm -->
    <div class="flex-shrink-0 mr-4">
      <img
        :src="productImage"
        :alt="item.name"
        class="w-20 h-20 object-cover rounded-md border border-gray-200"
        @error="$event.target.src = '/default-product.png'"
      />
    </div>

    <!-- Thông tin sản phẩm -->
    <div class="flex-grow min-w-0">
      <div class="flex items-start justify-between">
        <!-- Tên và thuộc tính -->
        <div class="flex-grow pr-4">
          <h3
            class="text-sm font-medium text-gray-900 line-clamp-2 hover:text-blue-500 cursor-pointer"
          >
            {{ item.name }}
          </h3>

          <!-- Thuộc tính variant -->
          <div v-if="variantAttributes.length > 0" class="mt-1">
            <div class="flex flex-wrap gap-1 text-xs text-gray-500">
              <span
                v-for="(attr, index) in variantAttributes"
                :key="index"
                class="bg-gray-100 px-2 py-1 rounded"
              >
                {{ attr.name }}: {{ attr.value }}
              </span>
            </div>
          </div>

          <!-- Phân loại hàng (nếu cần thay đổi) -->
          <div class="mt-2 relative">
            <button
              @click="openAttributeModal"
              class="text-xs text-gray-400 hover:text-orange-500 flex items-center"
              :disabled="isLoadingVariants"
            >
              <svg
                class="w-3 h-3 mr-1"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                />
              </svg>
              {{ isLoadingVariants ? "Đang tải..." : "Thay đổi" }}
            </button>

            <!-- Modal thay đổi phân loại -->
            <div
              v-if="showAttributeModal"
              class="fixed inset-0 bg-gray-200/30 flex items-center justify-center z-50"
              @click="closeAttributeModal"
            >
              <div
                class="bg-white rounded-lg p-6 max-w-md w-full mx-4 max-h-96 overflow-y-auto"
                @click.stop
              >
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-semibold text-gray-900">Chọn phân loại hàng</h3>
                  <button
                    @click="closeAttributeModal"
                    class="text-gray-400 hover:text-gray-600"
                  >
                    <svg
                      class="w-6 h-6"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>

                <!-- Loading state -->
                <div v-if="isLoadingVariants" class="text-center py-8">
                  <div
                    class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto"
                  ></div>
                  <p class="text-sm text-gray-500 mt-2">Đang tải variants...</p>
                </div>

                <!-- Content -->
                <div v-else class="space-y-4">
                  <div
                    v-for="attribute in uniqueAttributes"
                    :key="attribute.id"
                    class="space-y-2"
                  >
                    <label class="block text-sm font-medium text-gray-700">
                      {{ attribute.name }}
                    </label>

                    <div class="flex flex-wrap gap-2">
                      <button
                        v-for="option in getAvailableOptions(attribute.name)"
                        :key="option.value"
                        @click="
                          selectAttributeOption(attribute.name, option.value, option)
                        "
                        :class="[
                          'px-3 py-2 text-sm border rounded-md transition-colors relative',
                          selectedTempAttributes[attribute.name] === option.value
                            ? 'border-orange-500 bg-orange-50 text-orange-600'
                            : option.isCurrentVariant
                            ? 'border-blue-500 bg-blue-50 text-blue-600'
                            : option.inCart
                            ? 'border-red-200 bg-red-50 text-red-400 cursor-not-allowed'
                            : option.available
                            ? 'border-gray-300 bg-white text-gray-700 hover:border-orange-300'
                            : 'border-gray-200 bg-gray-50 text-gray-400 cursor-not-allowed',
                        ]"
                        :disabled="!option.available || option.inCart"
                        :title="
                          option.inCart
                            ? 'Đã có trong giỏ hàng'
                            : option.isCurrentVariant
                            ? 'Đang chọn'
                            : ''
                        "
                      >
                        {{ option.value }}

                        <!-- Badge cho variant hiện tại -->
                        <span
                          v-if="option.isCurrentVariant"
                          class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"
                          title="Đang chọn"
                        >
                          ✓
                        </span>

                        <!-- Badge cho variant đã có trong giỏ -->
                        <span
                          v-else-if="option.inCart"
                          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center"
                          title="Đã có trong giỏ hàng"
                        >
                          !
                        </span>
                      </button>
                    </div>
                  </div>

                  <!-- Thông tin variant được chọn -->
                  <div v-if="findSelectedVariant" class="bg-gray-50 p-3 rounded-md">
                    <div class="text-sm">
                      <div class="font-medium text-gray-900 mb-1">
                        Thông tin sản phẩm:
                      </div>
                      <div class="flex justify-between">
                        <span>Giá:</span>
                        <span class="font-medium text-orange-500">
                          ₫{{
                            parseFloat(findSelectedVariant.selling_price).toLocaleString()
                          }}
                        </span>
                      </div>
                      <div class="flex justify-between">
                        <span>Còn lại:</span>
                        <span
                          :class="
                            findSelectedVariant.quantity > 0
                              ? 'text-green-600'
                              : 'text-red-500'
                          "
                        >
                          {{ findSelectedVariant.quantity }} sản phẩm
                        </span>
                      </div>

                      <!-- Cảnh báo nếu variant đã có trong giỏ -->
                      <div
                        v-if="hasMatchingAttributesInCart(findSelectedVariant)"
                        class="text-red-500 text-xs mt-2 p-2 bg-red-50 rounded border border-red-200"
                      >
                        ⚠️ Sản phẩm này đã có trong giỏ hàng
                      </div>
                    </div>
                  </div>

                  <!-- Thông báo khi không thể thay đổi -->
                </div>

                <div class="flex justify-end gap-3 mt-6">
                  <button
                    @click="closeAttributeModal"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50"
                  >
                    Hủy
                  </button>
                  <button
                    @click="confirmAttributeChange"
                    :disabled="!canConfirmChange"
                    :class="[
                      'px-4 py-2 text-sm rounded-md transition-colors',
                      canConfirmChange
                        ? 'text-white bg-orange-500 hover:bg-orange-600'
                        : 'text-gray-400 bg-gray-300 cursor-not-allowed',
                    ]"
                  >
                    Xác nhận
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Giá -->
        <div class="text-right flex-shrink-0">
          <div v-if="hasDiscount" class="text-xs text-gray-400 line-through">
            ₫{{ itemOriginalPrice.toLocaleString() }}
          </div>
          <div class="text-sm font-semibold text-blue-500">
            ₫{{ itemPrice.toLocaleString() }}
          </div>
          <div v-if="hasDiscount" class="text-xs text-red-500 bg-red-50 px-1 rounded">
            -{{ discountPercent }}%
          </div>
        </div>
      </div>

      <!-- Số lượng và tổng tiền -->
      <div class="flex items-center justify-between mt-3">
        <!-- Controls số lượng -->
        <div class="flex items-center">
          <!-- Số lượng counter -->
          <div v-if="!isEditing" class="flex items-center border border-gray-300 rounded">
            <button
              @click="decreaseQuantity"
              :disabled="item.quantity <= 1"
              class="w-8 h-8 flex items-center justify-center text-gray-900 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M20 12H4"
                />
              </svg>
            </button>

            <div
              @click="startEdit"
              class="w-12 h-8 flex items-center justify-center text-sm cursor-pointer hover:bg-gray-50 select-none border-l border-r border-gray-300"
            >
              {{ item.quantity }}
            </div>

            <button
              @click="increaseQuantity"
              :disabled="item.quantity >= (item.variant?.quantity || 999)"
              class="w-8 h-8 flex items-center justify-center text-gray-900 hover:bg-gray-100 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                />
              </svg>
            </button>
          </div>

          <!-- Input editing mode -->
          <div v-else class="flex items-center gap-2">
            <input
              v-model="tempQuantity"
              type="number"
              min="1"
              :max="item.variant?.quantity || 999"
              class="w-16 h-8 text-center border border-blue-500 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"
              @keyup.enter="confirmEdit"
              @keyup.escape="cancelEdit"
              ref="quantityInput"
            />
            <button
              @click="confirmEdit"
              class="text-xs text-blue-500 hover:text-blue-600"
            >
              OK
            </button>
            <button @click="cancelEdit" class="text-xs text-gray-400 hover:text-gray-600">
              Hủy
            </button>
          </div>

          <!-- Xóa -->
          <button
            @click="removeItem"
            class="ml-4 text-xs text-gray-400 hover:text-red-500 flex items-center"
          >
            <svg
              class="w-3 h-3 mr-1"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
            Xóa
          </button>
        </div>

        <!-- Tổng tiền -->
        <div class="text-right">
          <span class="text-base font-semibold text-orange-500">Tổng tiền:</span>
          <div class="text-sm font-semibold text-blue-500">
            ₫{{ totalPrice.toLocaleString() }}
          </div>
          <div v-if="item.variant?.quantity <= 10" class="text-xs text-red-500 mt-1">
            Chỉ còn {{ item.variant.quantity }} sản phẩm
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
