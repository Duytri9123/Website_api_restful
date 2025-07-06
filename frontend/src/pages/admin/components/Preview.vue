<script setup>
import { initFlowbite } from "flowbite";
import { onMounted, reactive, ref, watch, computed, nextTick } from "vue";
import axiosAdmin from "../../../axiosAdmin.js";

const loading = ref(true);
const products = ref([]);
const categories = ref([]);
const brands = ref([]);
const error = ref(null);

const productImages = ref([]);
const productToDelete = ref(null);

const isAddModal = ref(false);
const isEditModal = ref(false);
const isPreviewModal = ref(false);
const isDelete = ref(false);

const form = reactive({
  id: null,
  name: "",
  description: "",
  short_description: "",
  status: "active",
  attributes: [],
  category_id: "",
  brand_id: "",
  variants: [],
});

const getAverageRating = (product) => {
  if (product.average_rating) {
    return parseFloat(product.average_rating).toFixed(1);
  }
  return "5.0";
};
const getReviewCount = (product) => {
  if (product.reviews_count) {
    return product.reviews_count;
  }
  return 0;
};
const renderStars = (rating) => {
  const numRating = parseFloat(rating);
  const fullStars = Math.floor(numRating);
  const hasHalfStar = numRating % 1 >= 0.5;
  const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

  return {
    full: fullStars,
    half: hasHalfStar,
    empty: emptyStars,
  };
};

const getImageUrl = (path) => `${axiosAdmin.defaults.baseURL}/storage/${path}`;

// Hàm xóa hình ảnh
const removeImage = async (index) => {
  if (index >= 0 && index < productImages.value.length) {
    const image = productImages.value[index];
    if (image.id) {
      form.deleted_images.push(image.id);
    }
    productImages.value.splice(index, 1);
  }
};
//preview
const openPreviewModal = async (product) => {
  isPreviewModal.value = true;
  isAddModal.value = false;
  isEditModal.value = false;
  form.id = product.id;
  form.name = product.name;
  form.selling_price = product.selling_price;
  form.original_price = product.original_price;
  form.quantity = product.quantity;
  form.description = product.description;
  form.short_description = product.short_description;
  form.weight = product.weight;
  form.status = product.status;
  form.dimensions = product.dimensions;
  form.category_id = product.category_id;
  form.brand_id = product.brand_id;

  productImages.value = product.product_images.map((img) => ({
    ...img,
    url: getImageUrl(img.image),
  }));
  form.deleted_images = [];
};

const closePreviewModal = async () => {
  isPreviewModal.value = false;
};
const openEditFromPreview = () => {
  // Kiểm tra xem có dữ liệu trong form không
  if (!form.id) {
    console.error("No product data available in form");
    return;
  }
  // Chuyển đổi trạng thái modal
  isPreviewModal.value = false;
  isEditModal.value = true;
  isAddModal.value = false;

  form.deleted_images = [];
};

// ham xoa
const openDelete = async (product) => {
  productToDelete.value = product;
  isDelete.value = true;
};
const closeDelete = async () => {
  isDelete.value = false;
  productToDelete.value = null;
};

const DeleteProduct = async () => {
  if (!productToDelete.value || !productToDelete.value.id) {
    console.error("No product selected for deletion");
    alert("No product selected for deletion!");
    return;
  }
  try {
    const response = await axiosAdmin.delete(`/api/products/${productToDelete.value.id}`);
    console.log("Product deleted successfully:", response.data);
    await fetchProducts();
    // Đóng modal và reset
    isDelete.value = false;
    productToDelete.value = null;

    alert("Sản phẩm đã được xóa thành công!");
  } catch (err) {
    console.error("Error deleting product:", err);
    error.value = "Failed to delete product. Please try again.";
  }
};

const fetchProducts = async () => {
  try {
    const response = await axiosAdmin.get("/api/products");
    products.value = response.data.data;
    nextTick(() => {
      initFlowbite();
    });
  } catch (err) {
    console.error("Error Fetching Products:", err);
    error.value = "Failed to fetch products. Please try again.";
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  try {
    const response = await axiosAdmin.get("/api/categories");
    categories.value = response.data.data;
  } catch (err) {
    console.error("Error Fetching Categories:", err);
    error.value = "Failed to fetch categories. Please try again.";
  }
};
const fetchBrands = async () => {
  try {
    const response = await axiosAdmin.get("/api/brands");
    brands.value = response.data.data;
  } catch (err) {
    console.error("Error Fetching Brands:", err);
    error.value = "Failed to fetch brands. Please try again.";
  }
};

onMounted(async () => {
  fetchProducts();
  fetchCategories();
  fetchBrands();
});
</script>
<template>
  <div>
    <!-- Preview Drawer -->
    <div
      v-if="isPreviewModal"
      class="fixed inset-0 z-50 flex justify-center items-center"
      @click.self="closePreviewModal"
    >
      <div
        @click.self="closePreviewModal"
        :class="[
          'overflow-y-auto fixed top-16 left-0 z-40 p-4 w-full max-w-lg h-[calc(100%-4rem)] bg-white transition-transform dark:bg-gray-800',
          { '-translate-x-0': isPreviewModal, '-translate-x-full': !isPreviewModal },
        ]"
        tabindex="-1"
      >
        <div>
          <h4
            class="mb-1.5 leading-none text-xl font-semibold text-gray-900 dark:text-white"
          >
            {{ form.name }}
          </h4>
          <h5 class="mb-2 text-xl font-bold text-gray-900 dark:text-white line-through">
            $ {{ form.original_price }}
          </h5>
          <h5 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
            $ {{ form.selling_price }}
          </h5>
        </div>
        <button
          @click.prevent="closePreviewModal"
          type="button"
          class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 absolute top-2.5 right-2.5 inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
        >
          <svg
            aria-hidden="true"
            class="w-5 h-5"
            fill="currentColor"
            viewbox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
          <span class="sr-only">Close menu</span>
        </button>
        <div class="grid grid-cols-3 gap-4 mb-4 sm:mb-5">
          <div
            v-for="(img, index) in productImages"
            :key="index"
            class="p-2 w-auto bg-gray-100 rounded-lg dark:bg-gray-700"
          >
            <img :src="img.url" alt="image" />
          </div>
        </div>
        <dl class="sm:mb-5">
          <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
            Details
          </dt>
          <dd class="mb-4 font-light text-gray-500 sm:mb-5 dark:text-gray-400">
            {{ form.description }}
          </dd>
        </dl>
        <dl class="grid grid-cols-2 gap-4 mb-4">
          <div
            class="col-span-2 p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 sm:col-span-1 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Shipping
            </dt>
            <dd class="flex items-center text-gray-500 dark:text-gray-400">
              <svg
                class="w-4 h-4 mr-1.5"
                aria-hidden="true"
                fill="currentColor"
                viewbox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                  clip-rule="evenodd"
                />
              </svg>
              United States, Europe
            </dd>
          </div>
          <div
            class="col-span-2 p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 sm:col-span-1 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Colors
            </dt>
            <dd
              class="flex items-center space-x-2 font-light text-gray-500 dark:text-gray-400"
            >
              <div class="flex-shrink-0 w-6 h-6 bg-purple-600 rounded-full"></div>
              <div class="flex-shrink-0 w-6 h-6 bg-indigo-400 rounded-full"></div>
              <div class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-600"></div>
              <div class="flex-shrink-0 w-6 h-6 bg-pink-400 rounded-full"></div>
              <div class="flex-shrink-0 w-6 h-6 bg-teal-300 rounded-full"></div>
              <div class="flex-shrink-0 w-6 h-6 bg-green-300 rounded-full"></div>
            </dd>
          </div>
          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Product State
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">
              <span
                class="bg-primary-100 text-primary-800 text font-medium inline-flex items-center px-2.5 py-0.5 rounded dark:bg-primary-200 dark:text-primary-800"
              >
                <svg
                  aria-hidden="true"
                  class="mr-1 w-5 h-5"
                  fill="currentColor"
                  viewbox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                  />
                </svg>
                {{ form.status }}
              </span>
            </dd>
          </div>
          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Category
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">
              {{ categories.find((category) => category.id === form.category_id)?.name }}
            </dd>
          </div>
          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Ships from
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">Flowbite</dd>
          </div>
          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Brand
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">
              {{ brands.find((brand) => brand.id === form.brand_id)?.name }}
            </dd>
          </div>

          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Dimensions (cm)
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">{{ form.dimensions }}</dd>
          </div>

          <div
            class="p-3 bg-gray-100 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
          >
            <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
              Item weight
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">{{ form.weight }}</dd>
          </div>
        </dl>
        <div class="flex bottom-0 left-0 justify-center pb-4 space-x-4 w-full">
          <button
            @click="openEditFromPreview()"
            type="button"
            class="text-white w-full inline-flex items-center justify-center bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800"
          >
            <svg
              aria-hidden="true"
              class="mr-1 -ml-1 w-5 h-5"
              fill="currentColor"
              viewbox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"
              />
              <path
                fill-rule="evenodd"
                d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                clip-rule="evenodd"
              />
            </svg>
            Edit
          </button>
          <button
            type="button"
            class="inline-flex w-full items-center text-white justify-center bg-red-600 hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900"
          >
            <svg
              aria-hidden="true"
              class="w-5 h-5 mr-1.5 -ml-1"
              fill="currentColor"
              viewbox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                fill-rule="evenodd"
                clip-rule="evenodd"
                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
              />
            </svg>
            Delete
          </button>
        </div>
      </div>
    </div>
    <!-- Delete Modal -->
    <div
      v-show="isDelete"
      tabindex="-1"
      @click.self="closeDelete()"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto bg-opacity-50"
    >
      <div class="relative w-full h-auto max-w-md max-h-full">
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
          <button
            @click="closeDelete()"
            type="button"
            class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-800 dark:hover:text-white"
          >
            <svg
              aria-hidden="true"
              class="w-5 h-5"
              fill="currentColor"
              viewbox="0 0 20 20"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              />
            </svg>
            <span class="sr-only">Close modal</span>
          </button>
          <div class="p-6 text-center">
            <svg
              aria-hidden="true"
              class="mx-auto mb-4 text-gray-400 w-14 h-14 dark:text-gray-200"
              fill="none"
              stroke="currentColor"
              viewbox="0 0 24 24"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <h3 class="mb-5 text-lg font-normal text-gray-500 dark:text-gray-400">
              Are you sure you want to delete this product?
            </h3>
            <button
              @click.prevent="DeleteProduct()"
              type="button"
              class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm inline-flex items-center px-5 py-2.5 text-center mr-2"
            >
              Yes, I'm sure
            </button>
            <button
              @click="closeDelete()"
              type="button"
              class="text-gray-500 bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
            >
              No, cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped></style>
