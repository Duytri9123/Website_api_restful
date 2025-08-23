<script setup>
import { onMounted, ref, computed } from "vue";
import axiosClient from "../../../axiosClient";

// Reactive state
const products = ref([]);
const error = ref(null);
const loading = ref(true);
const likedProducts = ref([]);

const getImageUrl = (path) =>
  path ? `${axiosClient.defaults.baseURL}/storage/${path}` : "";
const formatPrice = (price) => (price != null ? Number(price).toFixed(2) : "N/A");
// View counter
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

// Computed products to display
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

// Fetch products
const fetchProducts = async () => {
  try {
    loading.value = true;
    error.value = null;
    const [popularRes, allRes] = await Promise.all([
      axiosClient.get("api/products/popular"),
      axiosClient.get("api/products"),
    ]);
    const all = allRes.data.data || [];
    const popular = popularRes.data.data || [];

    console.log("All Products:" ,all);
    console.log("All popular",popular);

    const popularIds = new Set(popular.map((p) => p.product_id));

    const popularProducts = popular
      .map((p) => {
        const full = all.find((item) => item.id === p.product_id);
        return full && full.status === "active"
          ? {
              ...full,
              total_views: parseInt(p.total_views) || 0,
            }
          : null;
      })
      .filter(Boolean);

    const additional = all
      .filter(
        (p) =>
          p.status === "active" &&
          !popularIds.has(p.id) &&
          p.selling_price != null &&
          p.original_price != null
      )
      .slice(0, 10 - popularProducts.length)
      .map((p) => ({ ...p, total_views: 0 }));

    products.value = [...popularProducts, ...additional];

    console.log(products);
  } catch (err) {
    console.error("Error fetching products:", err);
    error.value = "Không thể tải danh sách sản phẩm nổi bật.";
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  fetchProducts();
});
</script>

<template>
  <div class="bg-gradient-to-br from-white to-white py-6">
    <div class="mx-auto max-w-7xl px-2 sm:px-2 lg:px-2">
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
          <h2 class="text-3xl font-bold text-gray-900">Sản phẩm nổi bật</h2>
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
      <div v-if="loading" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
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
        v-else
        class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
      >
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          class="group bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-blue-200"
        >
          <!-- Product Image -->
          <router-link :to="`/products/${product.id}`" class="relative overflow-hidden">
            <img
              v-if="product.thumbnail_image && product.thumbnail_image.url"
              :src="
                getImageUrl(
                  product.thumbnail_image.url
                )
              "
              :alt="product.name"
              class="w-full h-48 object-cover cursor-pointer transform group-hover:scale-105 transition-transform duration-500"
              @click="incrementView(product.id)"
              @error="handleImageError"
            />
            <div
              v-else
              class="w-full h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center"
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

            <!-- Discount Badge -->
            <div
              v-if="product.original_price > product.selling_price"
              class="absolute top-3 right-3 bg-gradient-to-r from-red-500 to-pink-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg"
            >
              -{{
                Math.round(
                  ((product.original_price - product.selling_price) /
                    product.original_price) *
                    100
                )
              }}%
            </div>
          </router-link>

          <!-- Product Info -->
          <div class="p-4">
            <h3
              class="text-sm font-semibold text-gray-900 line-clamp-2 mb-2 group-hover:text-blue-600 transition-colors duration-200"
            >
              <a
                :href="product.slug ? `/product/${product.slug}` : '#'"
                class="hover:underline"
              >
                {{ product.name }}
              </a>
            </h3>

            <!-- Price Section -->
            <div class="flex items-center justify-between">
              <div class="flex flex-col">
                <p
                  v-if="product.original_price > product.selling_price"
                  class="text-xs text-gray-400 line-through"
                >
                  ${{ formatPrice(product.original_price) }}
                </p>
                <p class="text-sm font-bold text-gray-900">
                  ${{ formatPrice(product.selling_price) }}
                </p>
              </div>

              <!-- Like Button -->
              <button
                @click="toggleLike(product.id)"
                class="p-2 rounded-full hover:bg-red-50 transition-colors duration-200"
                :class="
                  likedProducts.includes(product.id) ? 'text-red-500' : 'text-gray-400'
                "
              >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                  <path
                    d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"
                  />
                </svg>
              </button>
            </div>

            <!-- Rating (if available) -->
            <div v-if="product.product_reviews" class="flex items-center mt-2">
              <div class="flex text-yellow-400">
                <svg
                  v-for="i in 5"
                  :key="i"
                  class="w-3 h-3"
                  :class="
                    i <= (product.product_reviews.rating || 5)
                      ? 'text-yellow-400'
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
              <span class="text-xs text-gray-500 ml-1"
                >({{ product.product_reviews.rating || 0 }})</span
              >
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
        <p class="text-red-600 font-medium">{{ error }}</p>
        <button
          @click="fetchProducts"
          class="mt-4 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200"
        >
          Thử lại
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
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            ></path>
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
            ></path>
          </svg>
        </div>
        <p class="text-gray-500 text-lg font-medium">Chưa có sản phẩm nổi bật nào</p>
        <p class="text-gray-400 text-sm mt-1">
          Các sản phẩm sẽ xuất hiện ở đây khi có lượt xem
        </p>
      </div>
    </div>
  </div>
</template>
