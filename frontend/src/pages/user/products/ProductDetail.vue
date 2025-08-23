<script setup>
import { ref, onMounted, computed, nextTick, watch } from "vue";
import { useRoute } from "vue-router";
import { useCartStore } from "../../../stores/cart.js";
import axiosClient from "../../../axiosClient";

const route = useRoute();
const cartStore = useCartStore();

const product = ref({});
const error = ref(null);
const loading = ref(true);
const categories = ref([]);
const activeImage = ref(null);
const selectedVariant = ref(null);
const selectedVariantOptions = ref({}); // Track selected options for each attribute
const thumbnailContainer = ref(null);
const quantity = ref(1);
const isAddingToCart = ref(false);
const showSuccessMessage = ref(false);

// Zoom image
const mainImageContainer = ref(null);
const showMagnifier = ref(false);
const zoomLevel = 2;
const magnifierOffset = ref({ x: 0, y: 0 });
const backgroundPosition = ref({ x: 0, y: 0 });
const magnifierSize = 200;
const activeImageNaturalDimensions = ref({ width: 0, height: 0 });

// Breadcrumbs using API data structure
const getProductBreadcrumbsFromAPI = () => {
  const crumbs = [{ id: null, name: "Tất cả Danh mục" }];

  if (!product.value.category) {
    return crumbs;
  }
  const path = [];

  // If has parent category, add it first
  if (product.value.category.parent) {
    path.push({
      id: product.value.category.parent.id,
      name: product.value.category.parent.name,
    });
  }

  // Add current category
  path.push({
    id: product.value.category.id,
    name: product.value.category.name,
  });

  return crumbs.concat(path);
};

const productBreadcrumbs = computed(() => {
  if (!product.value.id || !product.value.category) {
    return [{ id: null, name: "Tất cả Danh mục" }];
  }

  return getProductBreadcrumbsFromAPI();
});

// Current price based on selected variant
const currentPrice = computed(() => {
  if (selectedVariant.value) {
    return {
      selling: selectedVariant.value.selling_price,
      original: selectedVariant.value.original_price,
    };
  }

  // If no variant selected but product has variants, show price range
  if (product.value.variants && product.value.variants.length > 0) {
    const prices = product.value.variants.map((v) => v.selling_price);
    const minPrice = Math.min(...prices);
    const maxPrice = Math.max(...prices);

    return {
      selling: minPrice === maxPrice ? minPrice : `${minPrice} - ${maxPrice}`,
      original: 0,
    };
  }

  return {
    selling: 0,
    original: 0,
  };
});

// Available variants grouped by attribute with smart filtering
const availableVariants = computed(() => {
  if (!product.value.variants) return {};

  const grouped = {};

  // First, get all attributes and their values
  product.value.variants.forEach((variant) => {
    variant.attribute_values.forEach((attr) => {
      if (!grouped[attr.attribute.name]) {
        grouped[attr.attribute.name] = [];
      }

      const existing = grouped[attr.attribute.name].find(
        (item) => item.value === attr.value
      );
      if (!existing) {
        grouped[attr.attribute.name].push({
          value: attr.value,
          code: attr.code,
          available: false, // Will be determined below
        });
      }
    });
  });

  // Now determine availability based on current selections
  Object.keys(grouped).forEach((attributeName) => {
    grouped[attributeName].forEach((option) => {
      // Check if this option is compatible with current selections
      const compatibleVariants = product.value.variants.filter((variant) => {
        // Variant must have this attribute value
        const hasThisAttribute = variant.attribute_values.some(
          (attr) => attr.attribute.name === attributeName && attr.value === option.value
        );

        if (!hasThisAttribute) return false;

        // Check if variant is compatible with other selected attributes
        const isCompatible = Object.entries(selectedVariantOptions.value).every(
          ([selectedAttr, selectedValue]) => {
            if (selectedAttr === attributeName) return true; // Skip current attribute

            return variant.attribute_values.some(
              (attr) =>
                attr.attribute.name === selectedAttr && attr.value === selectedValue
            );
          }
        );

        return isCompatible && variant.quantity > 0;
      });

      option.available = compatibleVariants.length > 0;
    });
  });

  return grouped;
});

// Find matching variant based on selected options
const findMatchingVariant = () => {
  if (!product.value.variants || Object.keys(selectedVariantOptions.value).length === 0) {
    return null;
  }

  // Only find variant if all attributes are selected
  const attributeNames = Object.keys(availableVariants.value);
  const selectedAttributeNames = Object.keys(selectedVariantOptions.value);

  // Check if all required attributes are selected
  if (attributeNames.length !== selectedAttributeNames.length) {
    return null;
  }

  return product.value.variants.find((variant) => {
    return Object.entries(selectedVariantOptions.value).every(
      ([attributeName, selectedValue]) => {
        return variant.attribute_values.some(
          (attr) => attr.attribute.name === attributeName && attr.value === selectedValue
        );
      }
    );
  });
};

// Current images based on selected variant or show all product images
const currentImages = computed(() => {
  if (selectedVariant.value && selectedVariant.value.images.length > 0) {
    return selectedVariant.value.images;
  }
  return product.value.images || [];
});

// Check if option is selected
const isOptionSelected = (attributeName, optionValue) => {
  return selectedVariantOptions.value[attributeName] === optionValue;
};

const handleBreadcrumbClick = (crumbId) => {
  console.log("Bạn đã click vào danh mục có ID:", crumbId);
};

const getImageUrl = (path) => {
  if (!path) {
    return "/path/to/default-placeholder-image.jpg";
  }
  return `${axiosClient.defaults.baseURL}/storage/${path}`;
};

const scrollThumbnails = (direction) => {
  if (thumbnailContainer.value) {
    const thumbnailWidth = thumbnailContainer.value.children[0].offsetWidth;
    const scrollAmount = thumbnailWidth + 8;

    thumbnailContainer.value.scrollBy({
      left: direction * scrollAmount,
      behavior: "smooth",
    });
  }
};

// Variant selection with toggle functionality
const selectVariant = (attributeName, selectedOption) => {
  if (!selectedOption.available) return;

  // If clicking the same option, toggle it off
  if (selectedVariantOptions.value[attributeName] === selectedOption.value) {
    delete selectedVariantOptions.value[attributeName];
  } else {
    // Select new option
    selectedVariantOptions.value[attributeName] = selectedOption.value;
  }

  // Update selected variant
  selectedVariant.value = findMatchingVariant();

  // Update active image
  if (selectedVariant.value && selectedVariant.value.images.length > 0) {
    activeImage.value = selectedVariant.value.images[0];
  } else if (currentImages.value.length > 0) {
    activeImage.value = currentImages.value[0];
  }
};

// Check if all required attributes are selected
const allAttributesSelected = computed(() => {
  const attributeNames = Object.keys(availableVariants.value);
  const selectedAttributeNames = Object.keys(selectedVariantOptions.value);
  return (
    attributeNames.length > 0 && attributeNames.length === selectedAttributeNames.length
  );
});
// Get available stock for current selection
const getAvailableStock = computed(() => {
  if (selectedVariant.value) {
    return selectedVariant.value.quantity;
  }

  // If no variant selected, show total stock
  if (product.value.variants && product.value.variants.length > 0) {
    return product.value.variants.reduce((total, variant) => total + variant.quantity, 0);
  }

  return 0;
});

// Check if product is available
const isProductAvailable = computed(() => {
  return product.value.status === "active" && getAvailableStock.value > 0;
});

// Quantity controls
const increaseQuantity = () => {
  const maxQuantity = selectedVariant.value
    ? selectedVariant.value.quantity
    : getAvailableStock.value;
  if (quantity.value < maxQuantity) {
    quantity.value++;
  }
};

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

const addToCart = async () => {
  if (!selectedVariant.value) {
    alert("Vui lòng chọn đầy đủ phiên bản sản phẩm trước khi thêm vào giỏ hàng");
    return;
  }

  if (selectedVariant.value.quantity <= 0) {
    alert("Sản phẩm này hiện đang hết hàng");
    return;
  }

  if (quantity.value > selectedVariant.value.quantity) {
    alert(`Chỉ còn ${selectedVariant.value.quantity} sản phẩm trong kho`);
    return;
  }

  isAddingToCart.value = true;

  try {
    console.log("product", product.value);
    console.log("variant", selectedVariant.value);
    console.log("quantity", quantity.value);

    cartStore.addToCart(product.value, selectedVariant.value, quantity.value);

    showSuccessMessage.value = true;
    setTimeout(() => {
      showSuccessMessage.value = false;
    }, 3000);
  } catch (err) {
    console.error("Error adding to cart:", err);
    error.value = "Không thể thêm sản phẩm vào giỏ hàng.";
  } finally {
    isAddingToCart.value = false;
  }
};

// Buy now functionality
const buyNow = async () => {
  if (!selectedVariant.value) {
    alert("Vui lòng chọn đầy đủ phiên bản sản phẩm trước khi mua");
    return;
  }

  if (selectedVariant.value.quantity <= 0) {
    alert("Sản phẩm này hiện đang hết hàng");
    return;
  }

  if (quantity.value > selectedVariant.value.quantity) {
    alert(`Chỉ còn ${selectedVariant.value.quantity} sản phẩm trong kho`);
    return;
  }

  // Add to cart first
  await addToCart();

  // Then redirect to checkout (you can customize this)
  if (showSuccessMessage.value) {
    setTimeout(() => {
      // Replace with your checkout route
      console.log("Redirecting to checkout...");
      // router.push('/checkout');
    }, 1000);
  }
};

// Magnifier functions
const handleMouseMove = (event) => {
  if (!activeImage.value || !mainImageContainer.value) {
    return;
  }

  const mainImageElement = mainImageContainer.value.querySelector("img");
  if (!mainImageElement) return;

  if (mainImageElement.naturalWidth === 0 || mainImageElement.naturalHeight === 0) {
    showMagnifier.value = false;
    return;
  }

  activeImageNaturalDimensions.value = {
    width: mainImageElement.naturalWidth,
    height: mainImageElement.naturalHeight,
  };

  const { left, top, width, height } = mainImageElement.getBoundingClientRect();
  const x = event.clientX - left;
  const y = event.clientY - top;

  if (x >= 0 && x <= width && y >= 0 && y <= height) {
    showMagnifier.value = true;
    let magnifierX = x + 20;
    let magnifierY = y + 20;

    if (magnifierX + magnifierSize > width) {
      magnifierX = x - magnifierSize - 20;
    }
    if (magnifierY + magnifierSize > height) {
      magnifierY = y - magnifierSize - 20;
    }
    if (magnifierX < 0) {
      magnifierX = x + 20;
    }
    if (magnifierY < 0) {
      magnifierY = y + 20;
    }

    magnifierOffset.value = {
      x: magnifierX,
      y: magnifierY,
    };

    const bgX =
      (x / width) *
      (activeImageNaturalDimensions.value.width * zoomLevel - magnifierSize);
    const bgY =
      (y / height) *
      (activeImageNaturalDimensions.value.height * zoomLevel - magnifierSize);

    backgroundPosition.value = {
      x: -bgX,
      y: -bgY,
    };
  } else {
    showMagnifier.value = false;
  }
};

const handleMouseLeave = () => {
  showMagnifier.value = false;
};

const magnifierStyle = computed(() => {
  if (!activeImage.value || activeImageNaturalDimensions.value.width === 0) {
    return {};
  }

  const imageUrl = getImageUrl(activeImage.value?.url || activeImage.value?.image);
  const scaledNaturalWidth = activeImageNaturalDimensions.value.width * zoomLevel;
  const scaledNaturalHeight = activeImageNaturalDimensions.value.height * zoomLevel;

  return {
    width: `${magnifierSize}px`,
    height: `${magnifierSize}px`,
    left: `${magnifierOffset.value.x}px`,
    top: `${magnifierOffset.value.y}px`,
    backgroundImage: `url(${imageUrl})`,
    backgroundSize: `${scaledNaturalWidth}px ${scaledNaturalHeight}px`,
    backgroundPosition: `${backgroundPosition.value.x}px ${backgroundPosition.value.y}px`,
    backgroundRepeat: "no-repeat",
    zIndex: 99,
  };
});

const fetchProductDetails = async () => {
  try {
    loading.value = true;
    error.value = null;
    const productId = route.params.id;

    if (!productId) {
      error.value = "Product ID is missing.";
      loading.value = false;
      return;
    }

    const response = await axiosClient.get(`api/products/${productId}`);
    product.value = response.data.data;
    // Reset selections
    selectedVariant.value = null;
    selectedVariantOptions.value = {};

    // Set active image to first product image
    if (product.value.images && product.value.images.length > 0) {
      activeImage.value = product.value.images[0];
    }

    nextTick(() => {
      const imgElement = mainImageContainer.value?.querySelector("img");
      if (imgElement && imgElement.complete) {
        activeImageNaturalDimensions.value = {
          width: imgElement.naturalWidth,
          height: imgElement.naturalHeight,
        };
      } else if (imgElement) {
        imgElement.onload = () => {
          activeImageNaturalDimensions.value = {
            width: imgElement.naturalWidth,
            height: imgElement.naturalHeight,
          };
        };
      }
    });
  } catch (err) {
    console.error("Error fetching product details:", err);
    error.value = "Không thể tải được chi tiết sản phẩm.";
  } finally {
    loading.value = false;
  }
};

// Watch for variant changes to update quantity limit
watch(selectedVariant, (newVariant) => {
  if (newVariant && quantity.value > newVariant.quantity) {
    quantity.value = Math.min(quantity.value, newVariant.quantity);
  }
});

onMounted(() => {
  fetchProductDetails();
  cartStore.loadFromLocalStorage();
});
</script>

<template>
  <div>
    <nav class="max-w-7xl mx-auto gap-8 lg:gap-12" aria-label="Breadcrumb">
      <ol role="list" class="flex font-bold items-center mt-4">
        <li
          v-for="(crumb, index) in productBreadcrumbs"
          :key="crumb.id || 'all-categories'"
        >
          <div class="flex items-center">
            <a
              @click.prevent="handleBreadcrumbClick(crumb.id)"
              :class="{
                'text-indigo-600 hover:text-indigo-800 cursor-pointer':
                  index < productBreadcrumbs.length - 1,
                'text-gray-500': index === productBreadcrumbs.length - 1,
              }"
              class="transition-colors duration-200"
            >
              {{ crumb.name }}
            </a>

            <svg
              v-if="index < productBreadcrumbs.length - 1"
              class="h-5 w-5 flex-shrink-0 text-gray-300 mx-1"
              fill="currentColor"
              viewBox="0 0 20 20"
              aria-hidden="true"
            >
              <path d="M5.555 17.776l8-16 .708.708-8 16-.708-.708z" />
            </svg>
          </div>
        </li>
      </ol>
    </nav>

    <div v-if="!loading && product && product.id" class="font-sans bg-white p-4 sm:p-8">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12">
        <!-- Image Section -->
        <div class="lg:col-span-5">
          <div
            ref="mainImageContainer"
            class="relative w-full pb-[100%] bg-blue-200 rounded-lg overflow-hidden mb-4 flex items-center justify-center group"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
          >
            <img
              v-if="activeImage"
              :src="getImageUrl(activeImage.url || activeImage.image)"
              :alt="product.name"
              class="absolute inset-0 w-full h-full object-cover rounded-lg"
            />
            <div
              v-else
              class="absolute inset-0 w-full h-full bg-gray-200 flex items-center justify-center rounded-lg"
            >
              <span class="text-gray-500">Không có ảnh</span>
            </div>

            <div
              v-if="showMagnifier && activeImage"
              class="absolute hidden lg:block border border-gray-300 rounded-lg overflow-hidden pointer-events-none z-20"
              :style="magnifierStyle"
            ></div>
          </div>

          <!-- Thumbnails -->
          <div class="relative">
            <div
              ref="thumbnailContainer"
              class="flex space-x-2 overflow-x-hidden scroll-smooth p-1"
            >
              <button
                v-for="(img, index) in currentImages"
                :key="index"
                @click="activeImage = img"
                class="relative flex-shrink-0 w-[calc(20%-8px)] h-0 pb-[calc(20%-8px)] rounded-lg overflow-hidden transition-all duration-300 ease-in-out transform"
                :class="{ 'ring-2 ring-red-300': activeImage === img }"
              >
                <img
                  :src="getImageUrl(img.url || img.image)"
                  :alt="'Thumbnail ' + (index + 1)"
                  class="absolute inset-0 w-full h-full object-cover rounded-lg transition-all duration-300 ease-in-out group-hover:opacity-90"
                />
              </button>
            </div>

            <button
              v-if="currentImages && currentImages.length > 5"
              @click="scrollThumbnails(-1)"
              class="absolute -left-4 top-1/2 -translate-y-1/2 bg-white rounded-full p-2 shadow-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10"
            >
              <svg
                class="h-5 w-5 text-gray-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                ></path>
              </svg>
            </button>

            <button
              v-if="currentImages && currentImages.length > 5"
              @click="scrollThumbnails(1)"
              class="absolute -right-4 top-1/2 -translate-y-1/2 bg-white rounded-full p-2 shadow-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10"
            >
              <svg
                class="h-5 w-5 text-gray-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                ></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Product Details Section -->
        <div class="space-y-6 lg:col-span-7">
          <!-- Status Badge -->
          <div>
            <span
              v-if="isProductAvailable"
              class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
            >
              Còn {{ getAvailableStock }} sản phẩm
            </span>
            <span
              v-else
              class="inline-block bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
            >
              Hết hàng
            </span>
          </div>

          <!-- Brand -->
          <div v-if="product.brand">
            <span class="text-sm text-gray-600">Thương hiệu: </span>
            <span class="text-sm font-medium text-indigo-600">{{
              product.brand.name
            }}</span>
          </div>

          <!-- Product Name -->
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ product.name }}</h1>

          <!-- Product Description -->
          <div v-if="product.short_description" class="text-gray-600">
            <p>{{ product.short_description }}</p>
          </div>

          <!-- SKU -->
          <div v-if="selectedVariant">
            <span class="text-sm text-gray-600">SKU: </span>
            <span class="text-sm font-medium">{{ selectedVariant.sku }}</span>
          </div>

          <!-- Pricing -->
          <div class="space-y-2">
            <div class="flex items-center space-x-3">
              <span class="text-2xl font-bold text-red-600">
                <span v-if="typeof currentPrice.selling === 'number'">
                  {{
                    new Intl.NumberFormat("vi-VN", {
                      style: "currency",
                      currency: "VND",
                    }).format(currentPrice.selling)
                  }}
                </span>
                <span v-else> {{ currentPrice.selling }} VND </span>
              </span>
              <span
                v-if="
                  selectedVariant &&
                  currentPrice.original &&
                  currentPrice.original > currentPrice.selling
                "
                class="text-lg text-gray-500 line-through"
              >
                {{
                  new Intl.NumberFormat("vi-VN", {
                    style: "currency",
                    currency: "VND",
                  }).format(currentPrice.original)
                }}
              </span>
            </div>

            <!-- Discount Badge -->
            <div
              v-if="
                selectedVariant &&
                currentPrice.original &&
                currentPrice.original > currentPrice.selling
              "
            >
              <span
                class="inline-block bg-red-100 text-red-800 text-sm font-semibold px-2 py-1 rounded"
              >
                Tiết kiệm
                {{
                  new Intl.NumberFormat("vi-VN", {
                    style: "currency",
                    currency: "VND",
                  }).format(currentPrice.original - currentPrice.selling)
                }}
              </span>
            </div>
          </div>

          <!-- Variant Selection -->
          <div
            v-for="(options, attributeName) in availableVariants"
            :key="attributeName"
            class="space-y-3"
          >
            <h3 class="text-sm font-medium text-gray-900">{{ attributeName }}</h3>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="option in options"
                :key="option.value"
                @click="selectVariant(attributeName, option)"
                :disabled="!option.available"
                class="px-4 py-2 border rounded-lg font-medium transition-all duration-200"
                :class="{
                  'border-indigo-600 bg-indigo-50 text-indigo-600': isOptionSelected(
                    attributeName,
                    option.value
                  ),
                  'border-gray-300 bg-white text-gray-700 hover:border-gray-400 hover:bg-gray-50':
                    !isOptionSelected(attributeName, option.value) && option.available,
                  'opacity-50 cursor-not-allowed border-gray-200 bg-gray-100 text-gray-700': !option.available,
                }"
              >
                {{ option.value }}
              </button>
            </div>
          </div>

          <!-- Quantity Selection -->
          <div v-if="selectedVariant" class="space-y-3">
            <h3 class="text-sm font-medium text-gray-900">Số lượng</h3>
            <div class="flex items-center space-x-3">
              <button
                @click="decreaseQuantity"
                :disabled="quantity <= 1"
                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 12H4"
                  ></path>
                </svg>
              </button>

              <span class="w-12 text-center font-medium">{{ quantity }}</span>

              <button
                @click="increaseQuantity"
                :disabled="!selectedVariant || quantity >= selectedVariant.quantity"
                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg
                  class="w-4 h-4"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                  ></path>
                </svg>
              </button>
            </div>
            <p class="text-xs text-gray-500">
              Còn lại: {{ selectedVariant.quantity }} sản phẩm
            </p>
          </div>

          <!-- Add to Cart Section -->
          <div class="space-y-4 xl:w-3/5 lg:w-4/5">
            <!-- Action Buttons -->
            <div class="flex space-x-3">
              <button
                @click="addToCart"
                :disabled="!selectedVariant || !isProductAvailable || isAddingToCart"
                class="flex-1 bg-indigo-500 text-white py-2 px-2 rounded-lg font-medium text-lg hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center space-x-2"
              >
                <svg
                  v-if="isAddingToCart"
                  class="animate-spin h-5 w-5"
                  fill="none"
                  viewBox="0 0 24 24"
                >
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                  ></circle>
                  <path
                    class="opacity-75"
                    fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                  ></path>
                </svg>
                <svg
                  v-else
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5L2 13M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"
                  ></path>
                </svg>
                <span>
                  {{
                    isAddingToCart
                      ? "Đang thêm..."
                      : !allAttributesSelected
                      ? "Thêm vào giỏ"
                      : !isProductAvailable
                      ? "Hết hàng"
                      : "Thêm vào giỏ"
                  }}
                </span>
              </button>

              <button
                @click="buyNow"
                :disabled="!selectedVariant || !isProductAvailable || isAddingToCart"
                class="flex-1 bg-red-600 text-white py-2 px-2 rounded-lg font-medium text-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center space-x-2"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M13 10V3L4 14h7v7l9-11h-7z"
                  ></path>
                </svg>
                <span>
                  {{
                    !allAttributesSelected
                      ? "Mua Ngay"
                      : !isProductAvailable
                      ? "Hết hàng"
                      : "Mua ngay"
                  }}
                </span>
              </button>
            </div>

            <!-- Success Message -->
            <div
              v-if="showSuccessMessage"
              class="bg-green-50 border border-green-200 rounded-lg p-3"
            >
              <div class="flex items-center">
                <svg
                  class="w-5 h-5 text-green-400 mr-2"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                >
                  <path
                    fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                    clip-rule="evenodd"
                  ></path>
                </svg>
                <p class="text-sm text-green-800">Đã thêm sản phẩm vào giỏ hàng!</p>
              </div>
            </div>
          </div>

          <!-- Additional Actions -->
          <div class="flex space-x-4 lg:w-3/5">
            <button
              class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 inline mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                ></path>
              </svg>
              Yêu thích
            </button>

            <button
              class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200"
            >
              <svg
                class="w-5 h-5 inline mr-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"
                ></path>
              </svg>
              Chia sẻ
            </button>
          </div>

          <!-- Product Details -->
          <div v-if="product.description" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Mô tả sản phẩm</h3>
            <div class="prose prose-sm text-gray-600">
              <p v-html="product.description.replace(/\r\n/g, '<br>')"></p>
            </div>
          </div>

          <!-- Product Specifications -->
          <div v-if="selectedVariant" class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Thông số kỹ thuật</h3>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Kích thước:</span>
                <span class="text-sm font-medium">{{
                  selectedVariant.dimensions || "N/A"
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">Trọng lượng:</span>
                <span class="text-sm font-medium">{{
                  selectedVariant.weight ? selectedVariant.weight + " kg" : "N/A"
                }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-sm text-gray-600">SKU:</span>
                <span class="text-sm font-medium">{{ selectedVariant.sku }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Product Reviews Section -->
      <div class="max-w-7xl mx-auto mt-12 border-t pt-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">Đánh giá sản phẩm</h2>
          <div class="flex items-center space-x-2">
            <div class="flex items-center">
              <svg
                v-for="n in 5"
                :key="n"
                class="w-5 h-5 text-yellow-400"
                fill="currentColor"
                viewBox="0 0 20 20"
              >
                <path
                  d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                />
              </svg>
            </div>
            <span class="text-sm text-gray-600">
              {{ product.avg_rating || 0 }} / 5 ({{ product.rating_count || 0 }} đánh giá)
            </span>
          </div>
        </div>

        <div class="text-center py-8 text-gray-500">
          <p>Chưa có đánh giá nào cho sản phẩm này.</p>
          <button
            class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Viết đánh giá đầu tiên
          </button>
        </div>
      </div>

      <!-- Related Products Section -->
      <div class="max-w-7xl mx-auto mt-12 border-t pt-8">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">Sản phẩm tương tự</h2>
        <div class="text-center py-8 text-gray-500">
          <p>Đang tải sản phẩm tương tự...</p>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="text-center py-12">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle
            class="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            stroke-width="4"
          ></circle>
          <path
            class="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
          ></path>
        </svg>
        <span class="ml-2 text-lg">Đang tải sản phẩm...</span>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="text-center py-12 text-red-500">
      <div class="flex flex-col items-center space-y-4">
        <svg
          class="w-12 h-12 text-red-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"
          />
        </svg>
        <p class="text-lg">{{ error }}</p>
        <button
          @click="fetchProductDetails"
          class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors"
        >
          Thử lại
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
