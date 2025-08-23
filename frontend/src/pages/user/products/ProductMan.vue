<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import axiosClient from "../../../axiosClient";

// Router instance
const router = useRouter();

// Reactive state
const products = ref([]);
const error = ref(null);
const loading = ref(true);
const likedProducts = ref(new Set());

// Helper functions
const getImageUrl = (path) =>
  path ? `${axiosClient.defaults.baseURL}/storage/${path}` : "";

const formatPrice = (price) => (price != null ? Number(price).toFixed(2) : "N/A");

// Get price from product variants
const getProductPrice = (product) => {
  if (product.variants && product.variants.length > 0) {
    // Get the cheapest variant price as selling price
    const validVariants = product.variants.filter((v) => v.selling_price != null);
    if (validVariants.length > 0) {
      const sellingPrices = validVariants.map((v) => Number(v.selling_price));
      const originalPrices = validVariants.map((v) =>
        Number(v.original_price || v.selling_price)
      );

      return {
        selling_price: Math.min(...sellingPrices),
        original_price: Math.min(...originalPrices),
        hasVariants: true,
      };
    }
  }

  // Fallback to product level prices
  return {
    selling_price: Number(product.selling_price) || 0,
    original_price: Number(product.original_price) || 0,
    hasVariants: false,
  };
};

// Handle image loading errors
const handleImageError = (event) => {
  event.target.style.display = "none";
  const placeholder = event.target.parentElement.querySelector(".image-placeholder");
  if (placeholder) {
    placeholder.style.display = "flex";
  }
};

// // View counter
const incrementView = async (id) => {
  try {
    const response = await axiosClient.post(
      `api/products/${id}/view`,
      {},
      {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      }
    );
  } catch (err) {
    console.error(
      "Error incrementing view count:",
      err.response?.data?.message || err.message
    );
    error.value = "Không thể tăng số lượt xem. Vui lòng thử lại.";
  }
};
// Like functionality
const toggleLike = (productId) => {
  if (likedProducts.value.has(productId)) {
    likedProducts.value.delete(productId);
  } else {
    likedProducts.value.add(productId);
  }
};

// Navigate to product detail
const navigateToProduct = async (product) => {
  await incrementView(product.id);
  // if (product.slug) {
  //   router.push(`/product/${product.slug}`);
  // } else {
  //   router.push(`/products/${product.id}`);
  // }
  router.push(`/products/${product.id}`);
};

// Computed products to display - sorted by ID (highest first)
const filteredProducts = computed(() => {
  return products.value
    .filter((p) => p?.status === "active")
    .map((p) => {
      const priceInfo = getProductPrice(p);
      return {
        ...p,
        original_price: priceInfo.original_price,
        selling_price: priceInfo.selling_price,
        hasVariants: priceInfo.hasVariants,
        hasDiscount: priceInfo.original_price > priceInfo.selling_price,
        discountPercentage:
          priceInfo.original_price > priceInfo.selling_price
            ? Math.round(
                ((priceInfo.original_price - priceInfo.selling_price) /
                  priceInfo.original_price) *
                  100
              )
            : 0,
        isNew: isHighestId(p.id),
      };
    })
    .filter((p) => p.selling_price > 0)
    .sort((a, b) => Number(b.id) - Number(a.id));
});

// Check if product has high ID (among top 10% of IDs)
const isHighestId = (productId) => {
  if (!products.value.length) return false;
  const allIds = products.value.map((p) => Number(p.id)).sort((a, b) => b - a);
  const topTenPercent = Math.ceil(allIds.length * 0.1);
  return allIds.slice(0, topTenPercent).includes(Number(productId));
};

// Fetch products - get all products sorted by ID
const fetchProducts = async () => {
  try {
    loading.value = true;
    error.value = null;

    // Fetch all products
    const response = await axiosClient.get("api/products");
    const allProducts = response.data.data || [];

    console.log("All products:", allProducts);

    // Xử lý lấy giá từ variants
    products.value = allProducts
      .map((p) => {
        if (p.variants && p.variants.length > 0) {
          // tìm biến thể có selling_price nhỏ nhất
          const cheapestVariant = p.variants.reduce((min, v) =>
            parseFloat(v.selling_price) < parseFloat(min.selling_price) ? v : min
          );

          return {
            ...p,
            selling_price: parseFloat(cheapestVariant.selling_price),
            original_price: parseFloat(cheapestVariant.original_price),
          };
        }
        return {
          ...p,
          selling_price: null,
          original_price: null,
        };
      })
      .filter((p) => {
        const isValid =
          p?.status === "active" && p.selling_price != null && p.original_price != null;
        if (!isValid) {
          console.log(`Product ${p.id} filtered out:`, {
            status: p.status,
            selling_price: p.selling_price,
            original_price: p.original_price,
          });
        }
        return isValid;
      })
      .sort((a, b) => Number(b.id) - Number(a.id));

    console.log("Filtered products sorted by ID:", products.value);
  } catch (err) {
    console.error("Error fetching products:", err);
    error.value = "Không thể tải danh sách sản phẩm.";
  } finally {
    loading.value = false;
  }
};

// Load liked products from localStorage on mount
const loadLikedProducts = () => {
  try {
    const saved = localStorage.getItem("likedProducts");
    if (saved) {
      const parsed = JSON.parse(saved);
      likedProducts.value = new Set(parsed);
    }
  } catch (err) {
    console.error("Error loading liked products:", err);
  }
};

// Save liked products to localStorage
const saveLikedProducts = () => {
  try {
    localStorage.setItem("likedProducts", JSON.stringify([...likedProducts.value]));
  } catch (err) {
    console.error("Error saving liked products:", err);
  }
};

// Toggle like with persistence
const toggleLikeWithPersistence = (productId) => {
  toggleLike(productId);
  saveLikedProducts();
};

onMounted(() => {
  loadLikedProducts();
  fetchProducts();
});
</script>

<template>
  <div class="bg-gradient-to-br from-white to-gray-50 py-6">
    <div class="mx-auto max-w-7xl px-2 sm:px-4 lg:px-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center space-x-3">
          <div
            class="w-10 h-10 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center"
          >
            <svg
              class="w-6 h-6 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
              ></path>
            </svg>
          </div>
          <h2 class="text-3xl font-bold text-gray-900">Sản phẩm Nam</h2>
        </div>
        <button
          @click="fetchProducts"
          class="group flex items-center space-x-2 text-blue-600 hover:text-blue-800 transition-colors duration-200"
        >
          <span class="text-sm font-medium">Xem tất cả</span>
          <svg
            class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200"
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

      <!-- Loading State -->
      <div
        v-if="loading"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
      >
        <div
          v-for="i in 10"
          :key="i"
          class="bg-white rounded-xl shadow-sm overflow-hidden animate-pulse"
        >
          <div class="h-48 bg-gray-200"></div>
          <div class="p-4 space-y-3">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
          </div>
        </div>
      </div>

      <!-- Products Grid -->
      <div
        v-else-if="!error"
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
      >
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-green-200"
        >
          <!-- Product Image -->
          <div
            class="relative overflow-hidden cursor-pointer"
            @click="navigateToProduct(product)"
          >
            <img
              v-if="product.thumbnail_image && product.thumbnail_image.url"
              :src="getImageUrl(product.thumbnail_image.url)"
              :alt="product.name"
              class="w-full h-48 object-cover transform group-hover:scale-105 transition-transform duration-500"
              @error="handleImageError"
            />
            <div
              v-else
              class="image-placeholder w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
            >
              <svg
                class="w-12 h-12 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                ></path>
              </svg>
            </div>

            <!-- New Badge -->
            <div
              v-if="product.isNew"
              class="absolute top-3 left-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg"
            >
              MỚI
            </div>

            <!-- Discount Badge -->
            <div
              v-if="product.hasDiscount"
              class="absolute top-3 right-3 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg"
            >
              -{{ product.discountPercentage }}%
            </div>
            <!-- Like Button -->
            <button
              @click.stop="toggleLikeWithPersistence(product.id)"
              class="absolute bottom-3 right-3 p-2 rounded-full bg-white shadow-md hover:bg-red-50 transition-all duration-200 transform hover:scale-110"
              :class="
                likedProducts.has(product.id)
                  ? 'text-red-500'
                  : 'text-gray-400 hover:text-red-400'
              "
            >
              <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                />
              </svg>
            </button>
          </div>

          <!-- Product Info -->
          <div class="p-4">
            <h3
              class="text-sm font-semibold text-gray-900 line-clamp-2 mb-2 group-hover:text-green-600 transition-colors duration-200 cursor-pointer"
              @click="navigateToProduct(product)"
            >
              {{ product.name }}
            </h3>

            <!-- Price Section -->
            <div class="flex items-center justify-between mb-2">
              <div class="flex flex-col">
                <p v-if="product.hasDiscount" class="text-sm text-gray-400 line-through">
                  ${{ formatPrice(product.original_price) }}
                </p>
                <p class="text-sm font-bold text-gray-900">
                  ${{ formatPrice(product.selling_price) }}
                </p>
              </div>

              <!-- Cart Button -->
              <button
                @click.stop="handleAddToCart(product)"
                class="p-2 rounded-full hover:bg-blue-50 transition-all duration-200 transform hover:scale-110 text-gray-400 hover:text-blue-600"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  width="24"
                  height="24"
                  viewBox="0 0 24 24"
                >
                  <path
                    fill="currentColor"
                    d="M0 1h4.764l3 11h10.515l3.088-9.265l1.898.633L19.72 14H7.78l-.5 2H22v2H4.72l1.246-4.989L3.236 3H0V1Zm14 1v3h3v2h-3v3h-2V7H9V5h3V2h2ZM4 21a2 2 0 1 1 4 0a2 2 0 0 1-4 0Zm14 0a2 2 0 1 1 4 0a2 2 0 0 1-4 0Z"
                  />
                </svg>
              </button>
            </div>

            <!-- Rating -->
            <div v-if="product.avg_rating > 0" class="flex items-center mt-2">
              <div class="flex text-yellow-400">
                <svg
                  v-for="i in 5"
                  :key="i"
                  class="w-3 h-3"
                  :class="
                    i <= Math.floor(product.avg_rating)
                      ? 'text-yellow-400'
                      : i <= product.avg_rating
                      ? 'text-yellow-300'
                      : 'text-gray-300'
                  "
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                  />
                </svg>
              </div>
              <span class="text-xs text-gray-500 ml-1">
                {{ product.avg_rating.toFixed(1) }} ({{ product.rating_count || 0 }})
              </span>
            </div>

            <!-- Alternative: Show placeholder when no rating -->
            <div v-else class="flex items-center mt-2">
              <div class="flex text-gray-300">
                <svg
                  v-for="i in 5"
                  :key="i"
                  class="w-3 h-3"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                  />
                </svg>
              </div>
              <span class="text-xs text-gray-400 ml-1">Chưa có đánh giá</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="text-center py-12">
        <div
          class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-4"
        >
          <svg
            class="w-8 h-8 text-red-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            ></path>
          </svg>
        </div>
        <p class="text-red-600 font-medium mb-4">{{ error }}</p>
        <button
          @click="fetchProducts"
          :disabled="loading"
          class="px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ loading ? "Đang thử lại..." : "Thử lại" }}
        </button>
      </div>

      <!-- Empty State -->
      <div
        v-if="!loading && !error && filteredProducts.length === 0"
        class="text-center py-12"
      >
        <div
          class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4"
        >
          <svg
            class="w-8 h-8 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
            ></path>
          </svg>
        </div>
        <p class="text-gray-500 text-lg font-medium">Chưa có sản phẩm nào</p>
        <p class="text-gray-400 text-sm mt-1">Các sản phẩm sẽ xuất hiện ở đây</p>
        <button
          @click="fetchProducts"
          class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors duration-200"
        >
          Tải lại
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped></style>
