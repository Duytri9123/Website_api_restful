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
      <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
        <!-- Image Section -->
        <div>
          <div
            ref="mainImageContainer"
            class="relative w-full pb-[100%] bg-blue-200 rounded-lg overflow-hidden mb-4 flex items-center justify-center group"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
          >
            <img
              v-if="activeImage"
              :src="getImageUrl(activeImage.image_url || activeImage.image)"
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
            >
            </div>
          </div>
          
          <!-- Thumbnails -->
          <div class="relative">
            <div
              ref="thumbnailContainer"
              class="flex space-x-2 overflow-x-hidden scroll-smooth p-1"
            >
              <button
                v-for="(img, index) in product.product_images"
                :key="index"
                @click="activeImage = img"
                class="relative flex-shrink-0 w-[calc(20%-8px)] h-0 pb-[calc(20%-8px)] rounded-lg overflow-hidden
                       transition-all duration-300 ease-in-out transform"
                :class="{ 'ring-2 ring-red-300': activeImage === img }"
              >
                <img
                  :src="getImageUrl(img.image_url || img.image)"
                  :alt="'Thumbnail ' + (index + 1)"
                  class="absolute inset-0 w-full h-full object-cover rounded-lg
                         transition-all duration-300 ease-in-out group-hover:opacity-90"
                />
              </button>
            </div>

            <button
              v-if="product.product_images && product.product_images.length > 5"
              @click="scrollThumbnails(-1)"
              class="absolute -left-4 top-1/2 -translate-y-1/2 bg-white rounded-full p- shadow-md
                     hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10"
            >
              <svg
                class="h-5 w-5 text-gray-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
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
              v-if="product.product_images && product.product_images.length > 5"
              @click="scrollThumbnails(1)"
              class="absolute -right-4 top-1/2 -translate-y-1/2 bg-white rounded-full p-2 shadow-md
                     hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10"
            >
              <svg
                class="h-5 w-5 text-gray-700"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
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
        <div class="space-y-6">
          <!-- Status Badge -->
          <div>
            <span
              v-if="product.status == 'active'"
              class="inline-block bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
            >
              In stock
            </span>
            <span
              v-else
              class="inline-block bg-red-100 text-red-800 text-xs font-semibold px-2.5 py-0.5 rounded-full"
            >
              Out of stock
            </span>
          </div>

          <!-- Product Name -->
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">{{ product.name }}</h1>

          <!-- Product Description -->
          <div v-if="product.description" class="text-gray-600">
            <p>{{ product.description }}</p>
          </div>

          <!-- Pricing -->
          <div class="space-y-2">
            <div class="flex items-center space-x-3">
              <span class="text-2xl font-bold text-red-600">
                ${{ product.selling_price }}
              </span>
              <span v-if="product.original_price && product.original_price > product.selling_price" 
                    class="text-lg text-gray-500 line-through">
                ${{ product.original_price }}
              </span>
            </div>
            
            <!-- Discount Badge -->
            <div v-if="product.original_price && product.original_price > product.selling_price">
              <span class="inline-block bg-red-100 text-red-800 text-sm font-semibold px-2 py-1 rounded">
                Save ${{ (product.original_price - product.selling_price).toFixed(2) }}
              </span>
            </div>
          </div>

          <!-- Color Selection (if available) -->
          <div v-if="product.colors && product.colors.length > 0" class="space-y-3">
            <h3 class="text-sm font-medium text-gray-900">Color</h3>
            <div class="flex space-x-2">
              <button
                v-for="color in product.colors"
                :key="color.id"
                @click="selectedColor = color"
                class="w-8 h-8 rounded-full border-2 transition-all duration-200"
                :class="{
                  'border-gray-900 ring-2 ring-gray-900 ring-offset-2': selectedColor?.id === color.id,
                  'border-gray-300 hover:border-gray-400': selectedColor?.id !== color.id
                }"
                :style="{ backgroundColor: color.hex_code || color.name }"
                :title="color.name"
              ></button>
            </div>
            <p class="text-sm text-gray-600">Selected: {{ selectedColor?.name }}</p>
          </div>

          <!-- Quantity Selection -->
          <div class="space-y-3">
            <h3 class="text-sm font-medium text-gray-900">Quantity</h3>
            <div class="flex items-center space-x-3">
              <button
                @click="decreaseQuantity"
                :disabled="quantity <= 1"
                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                </svg>
              </button>
              
              <span class="w-12 text-center font-medium">{{ quantity }}</span>
              
              <button
                @click="increaseQuantity"
                class="w-8 h-8 rounded-full border border-gray-300 flex items-center justify-center hover:bg-gray-50"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Add to Cart Section -->
          <div class="space-y-4">
            <button
              @click="addToCart"
              :disabled="product.status !== 'active' || isAddingToCart"
              class="w-full bg-indigo-600 text-white py-3 px-6 rounded-lg font-medium text-lg
                     hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                     disabled:bg-gray-400 disabled:cursor-not-allowed transition-all duration-200
                     flex items-center justify-center space-x-2"
            >
              <svg v-if="isAddingToCart" class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5L2 13M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6"></path>
              </svg>
              <span>
                {{ isAddingToCart ? 'Adding...' : product.status === 'active' ? 'Add to Cart' : 'Out of Stock' }}
              </span>
            </button>

            <!-- Success Message -->
            <div v-if="showSuccessMessage" class="bg-green-50 border border-green-200 rounded-lg p-3">
              <div class="flex items-center">
                <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <p class="text-sm text-green-800">Product added to cart successfully!</p>
              </div>
            </div>
          </div>

          <!-- Additional Actions -->
          <div class="flex space-x-4">
            <button class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
              <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
              </svg>
              Add to Wishlist
            </button>
            
            <button class="flex-1 border border-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors duration-200">
              <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.367 2.684 3 3 0 00-5.367-2.684z"></path>
              </svg>
              Share
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="loading" class="text-center py-12">
      <div class="flex items-center justify-center">
        <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="ml-2 text-lg">Loading product...</span>
      </div>
    </div>

    <div v-if="error" class="text-center py-12 text-red-500">
      <p class="text-lg">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed, nextTick } from "vue"; 
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
const selectedColor = ref(null);
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
const magnifierSize = 150; 
const activeImageNaturalDimensions = ref({ width: 0, height: 0 });

const getProductBreadcrumbs = (categoryId, allCategories) => {
  const crumbs = [{ id: null, name: "Tất cả Danh mục" }];
  let currentId = categoryId;
  const path = [];

  while (currentId) {
    const cat = allCategories.find((c) => c.id === currentId);
    if (cat) {
      path.unshift(cat);
      currentId = cat.parent_id;
    } else {
      currentId = null;
    }
  }
  return crumbs.concat(path);
};

const productBreadcrumbs = computed(() => {
  return getProductBreadcrumbs(product.value.category_id, categories.value);
});

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

// Quantity controls
const increaseQuantity = () => {
  quantity.value++;
};

const decreaseQuantity = () => {
  if (quantity.value > 1) {
    quantity.value--;
  }
};

// Add to cart functionality
const addToCart = async () => {
  if (product.value.status !== 'active') {
    return;
  }

  isAddingToCart.value = true;
  
  try {
    // Prepare product data for cart
    const cartProduct = {
      id: product.value.id,
      name: product.value.name,
      price: product.value.selling_price,
      image: product.value.product_images?.[0]?.image_url || product.value.product_images?.[0]?.image,
      color: selectedColor.value?.name,
      quantity: quantity.value
    };

    // Add to cart store
    for (let i = 0; i < quantity.value; i++) {
      cartStore.addToCart({
        ...cartProduct,
        quantity: 1 // Add one at a time to handle duplicates properly
      });
    }

    // Show success message
    showSuccessMessage.value = true;
    setTimeout(() => {
      showSuccessMessage.value = false;
    }, 3000);

    // Optional: API call to sync with backend
    // await axiosClient.post('/api/cart/add', cartProduct);

  } catch (err) {
    console.error('Error adding to cart:', err);
    error.value = 'Failed to add product to cart. Please try again.';
  } finally {
    isAddingToCart.value = false;
  }
};

// Magnifier functions
const handleMouseMove = (event) => {
  if (!activeImage.value || !mainImageContainer.value) {
    return;
  }

  const mainImageElement = mainImageContainer.value.querySelector('img');
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

    const bgX = (x / width) * (activeImageNaturalDimensions.value.width * zoomLevel - magnifierSize);
    const bgY = (y / height) * (activeImageNaturalDimensions.value.height * zoomLevel - magnifierSize);

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

  const imageUrl = getImageUrl(activeImage.value?.image_url || activeImage.value?.image);
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
    backgroundRepeat: 'no-repeat',
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
    product.value = response.data;

    if (product.value.product_images && product.value.product_images.length > 0) {
      activeImage.value = product.value.product_images[0];

      nextTick(() => {
        const imgElement = mainImageContainer.value?.querySelector('img');
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
    }

    if (product.value.colors && product.value.colors.length > 0) {
      selectedColor.value = product.value.colors[0];
    }
  } catch (err) {
    console.error("Error fetching product details:", err);
    error.value = "Không thể tải được chi tiết sản phẩm.";
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await axiosClient.get("/api/categories");
    categories.value = response.data;
  } catch (err) {
    console.error("Error fetching categories:", err);
    error.value = "Unable to load categories. Please try again later.";
  }
};

onMounted(() => {
  fetchCategories();
  fetchProductDetails();
  cartStore.loadFromLocalStorage(); // Load cart data on component mount
});
</script>