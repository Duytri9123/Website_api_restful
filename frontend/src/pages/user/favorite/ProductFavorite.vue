<script setup>
import { onMounted, ref, computed } from "vue";
import { useRouter } from "vue-router";
import { useFavoriteStore } from "../../../stores/favorite"; // import store
import axiosClient from "../../../axiosClient";

const router = useRouter();
const favoriteStore = useFavoriteStore();

const products = ref([]);
const error = ref(null);
const loading = ref(true);

// Lấy danh sách sản phẩm yêu thích
const favoriteProducts = computed(() => {
  return products.value.filter((p) => favoriteStore.isFavorite(p.id));
});

// Điều hướng sang chi tiết sản phẩm
const navigateToProduct = (product) => {
  router.push(`/products/${product.id}`);
};

// Fetch tất cả sản phẩm rồi lọc lại
const fetchProducts = async () => {
  try {
    loading.value = true;
    error.value = null;

    const response = await axiosClient.get("api/products");
    products.value = response.data.data || [];
  } catch (err) {
    console.error("Error fetching products:", err);
    error.value = "Không thể tải danh sách sản phẩm.";
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  favoriteStore.loadFromLocalStorage();
  fetchProducts();
});
</script>

<template>
  <div class="max-w-7xl mx-auto p-4">
    <h2 class="text-2xl font-bold mb-6">❤️ Sản phẩm yêu thích</h2>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-10">Đang tải...</div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-10 text-red-500">
      {{ error }}
    </div>

    <!-- Empty -->
    <div v-else-if="!favoriteProducts.length" class="text-center py-10 text-gray-500">
      Bạn chưa có sản phẩm nào trong danh sách yêu thích.
    </div>

    <!-- Products -->
    <div
      v-else
      class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
    >
      <div
        v-for="product in favoriteProducts"
        :key="product.id"
        class="group bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-100"
      >
        <div
          class="relative overflow-hidden cursor-pointer"
          @click="navigateToProduct(product)"
        >
          <img
            v-if="product.thumbnail_image?.url"
            :src="
              axiosClient.defaults.baseURL + '/storage/' + product.thumbnail_image.url
            "
            :alt="product.name"
            class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300"
          />

          <div v-else class="w-full h-48 bg-gray-100 flex items-center justify-center">
            <span class="text-gray-400">No Image</span>
          </div>
        </div>

        <div class="p-4">
          <h3
            class="text-sm font-semibold text-gray-900 hover:text-blue-500 cursor-pointer line-clamp-2 mb-2"
            @click="navigateToProduct(product)"
          >
            {{ product.name }}
          </h3>
          <p class="text-sm font-bold text-green-600">${{ product.selling_price }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
