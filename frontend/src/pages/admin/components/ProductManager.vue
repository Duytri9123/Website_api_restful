<script setup>
import { onMounted, reactive, ref, watch, computed, nextTick } from "vue";
import FormProducts from "./FormProducts.vue";
import axiosAdmin from "../../../axiosAdmin.js";

const loading = ref(true);
const error = ref(null);

// state
const products = ref([]);
const categories = ref([]);
const brands = ref([]);
const attributes = ref([]);

const showProductForm = ref(false);
const editingProductData = ref(null);
const isEditingProduct = ref(false);
const getImageUrl = (path) => `${axiosAdmin.defaults.baseURL}/storage/${path}`;
//table
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

//form product
const openAddProduct = () => {
  editingProductData.value = null;
  isEditingProduct.value = false;
  showProductForm.value = true;
  error.value = null;
};

const openEditProduct = async (product) => {
  error.value = null;
  try {
    const response = await axiosAdmin.get(`/api/products/${product.id}`);
    const fullProductData = response.data.data;

    editingProductData.value = fullProductData;
    isEditingProduct.value = true;
    showProductForm.value = true; 

    console.log("Full product data fetched for editing:", fullProductData);
  } catch (err) {
    console.error("Lỗi khi tải chi tiết sản phẩm để chỉnh sửa:", err);
    error.value = "Không thể tải chi tiết sản phẩm. Vui lòng thử lại.";
  }
};

const deleteProduct = async (productId) => {
  if (!confirm("Are you sure you want to delete this product?")) {
    return;
  }
  try {
    await axiosAdmin.delete(`/api/products/${productId}`);
    alert("Product deleted successfully!");
    fetchProducts(); // Refresh the list after deletion
  } catch (err) {
    console.error("Error deleting product:", err);
    error.value = "Failed to delete product. Please try again.";
  }
};

const handleProductSubmitted = () => {
  showProductForm.value = false;
  fetchProducts();
  alert("Thao tác sản phẩm thành công!");
};
const handleFormClosed = () => {
  showProductForm.value = false;
  editingProductData.value = null;
  isEditingProduct.value = false;
  error.value = null;
};

const handleRequestRefreshAttributes = () => {
  fetchAttributes();
};
//end form 

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

const fetchAttributes = async () => {
  try {
    const response = await axiosAdmin.get("/api/attributes");
    attributes.value = response.data.data;
    console.log("Attributes:", attributes.value);
  } catch (err) {
    console.error("Error Fetching Attributes:", err);
    error.value = "Failed to fetch attributes. Please try again.";
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
  fetchAttributes();
});
</script>

<template>
  <div>
    <section class="bg-white dark:bg-gray-900 p-6 sm:p-8 antialiased">
      <div class="mx-auto max-w-screen-2xl px-4 lg:px-12">
        <div
          class="bg-white dark:bg-gray-800 relative shadow-md sm:rounded-lg overflow-hidden"
        >
          <div
            class="flex flex-col md:flex-row items-stretch md:items-center md:space-x-3 space-y-3 md:space-y-0 justify-between mx-4 py-4 border-t dark:border-gray-700"
          >
            <!-- search -->
            <div class="w-full md:w-1/2">
              <form class="flex items-center">
                <label for="simple-search" class="sr-only">Search</label>
                <div class="relative w-full">
                  <div
                    class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none"
                  >
                    <svg
                      aria-hidden="true"
                      class="w-5 h-5 text-gray-500 dark:text-gray-400"
                      fill="currentColor"
                      viewbox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        clip-rule="evenodd"
                        d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                      />
                    </svg>
                  </div>
                  <input
                    type="text"
                    id="simple-search"
                    placeholder="Search for products"
                    required=""
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  />
                </div>
              </form>
            </div>
            <div
              class="w-full md:w-auto flex flex-col md:flex-row space-y-2 md:space-y-0 items-stretch md:items-center justify-end md:space-x-3 flex-shrink-0"
            >
              <!-- add product -->
              <button
                type="button"
                @click.prevent="openAddProduct"
                class="flex items-center justify-center text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
              >
                <svg
                  class="h-3.5 w-3.5 mr-1.5 -ml-1"
                  fill="currentColor"
                  viewbox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-hidden="true"
                >
                  <path
                    clip-rule="evenodd"
                    fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                  />
                </svg>
                Add product
              </button>

              <!-- filter -->
              <div
                id="filterDropdown"
                class="z-10 hidden px-3 pt-1 bg-white rounded-lg shadow w-80 dark:bg-gray-700 right-0"
              >
                <div class="flex items-center justify-between pt-2">
                  <h6 class="text-sm font-medium text-black dark:text-white">Filters</h6>
                  <div class="flex items-center space-x-3">
                    <a
                      href="#"
                      class="flex items-center text-sm font-medium text-primary-600 dark:text-primary-500 hover:underline"
                      >Save view</a
                    >
                    <a
                      href="#"
                      class="flex items-center text-sm font-medium text-primary-600 dark:text-primary-500 hover:underline"
                      >Clear all</a
                    >
                  </div>
                </div>
                <div class="pt-3 pb-2">
                  <label for="input-group-search" class="sr-only">Search</label>
                  <div class="relative">
                    <div
                      class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none"
                    >
                      <svg
                        class="w-4 h-4 text-gray-500 dark:text-gray-400"
                        aria-hidden="true"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                          clip-rule="evenodd"
                        />
                      </svg>
                    </div>
                    <input
                      type="text"
                      id="input-group-search"
                      class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Search keywords..."
                    />
                  </div>
                </div>
                <div
                  id="accordion-flush"
                  data-accordion="collapse"
                  data-active-classes="text-black dark:text-white"
                  data-inactive-classes="text-gray-500 dark:text-gray-400"
                >
                  <!-- Category -->
                  <h2 id="category-heading">
                    <button
                      type="button"
                      class="flex items-center justify-between w-full py-2 px-1.5 text-sm font-medium text-left text-gray-500 border-b border-gray-200 dark:border-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                      data-accordion-target="#category-body"
                      aria-expanded="true"
                      aria-controls="category-body"
                    >
                      <span>Category</span>
                      <svg
                        aria-hidden="true"
                        data-accordion-icon=""
                        class="w-5 h-5 rotate-180 shrink-0"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        />
                      </svg>
                    </button>
                  </h2>
                  <div
                    id="category-body"
                    class="hidden"
                    aria-labelledby="category-heading"
                  >
                    <div
                      class="py-2 font-light border-b border-gray-200 dark:border-gray-600"
                    >
                      <ul class="space-y-2">
                        <li class="flex items-center">
                          <input
                            id="apple"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="apple"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Apple (56)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="microsoft"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="microsoft"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Microsoft (45)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="logitech"
                            type="checkbox"
                            value=""
                            checked=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="logitech"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Logitech (97)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="sony"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="sony"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Sony (234)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="asus"
                            type="checkbox"
                            value=""
                            checked=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="asus"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Asus (97)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="dell"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="dell"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Dell (56)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="msi"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="msi"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >MSI (97)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="canon"
                            type="checkbox"
                            value=""
                            checked=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="canon"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Canon (49)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="benq"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="benq"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >BenQ (23)</label
                          >
                        </li>
                        <li class="flex items-center">
                          <input
                            id="razor"
                            type="checkbox"
                            value=""
                            class="w-4 h-4 bg-gray-100 border-gray-300 rounded text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                          />
                          <label
                            for="razor"
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-100"
                            >Razor (49)</label
                          >
                        </li>
                        <a
                          href="#"
                          class="flex items-center text-sm font-medium text-primary-600 dark:text-primary-500 hover:underline"
                          >View all</a
                        >
                      </ul>
                    </div>
                  </div>
                  <!-- Price -->
                  <h2 id="price-heading">
                    <button
                      type="button"
                      class="flex items-center justify-between w-full py-2 px-1.5 text-sm font-medium text-left text-gray-500 border-b border-gray-200 dark:border-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                      data-accordion-target="#price-body"
                      aria-expanded="true"
                      aria-controls="price-body"
                    >
                      <span>Price</span>
                      <svg
                        aria-hidden="true"
                        data-accordion-icon=""
                        class="w-5 h-5 rotate-180 shrink-0"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        />
                      </svg>
                    </button>
                  </h2>
                  <div id="price-body" class="hidden" aria-labelledby="price-heading">
                    <div
                      class="flex items-center py-2 space-x-3 font-light border-b border-gray-200 dark:border-gray-600"
                    >
                      <select
                        id="price-from"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      >
                        <option disabled="" selected="">From</option>
                        <option>$500</option>
                        <option>$2500</option>
                        <option>$5000</option></select
                      ><select
                        id="price-to"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      >
                        <option disabled="" selected="">To</option>
                        <option>$500</option>
                        <option>$2500</option>
                        <option>$5000</option>
                      </select>
                    </div>
                  </div>
                  <!-- Worldwide Shipping -->
                  <h2 id="worldwide-shipping-heading">
                    <button
                      type="button"
                      class="flex items-center justify-between w-full py-2 px-1.5 text-sm font-medium text-left text-gray-500 border-b border-gray-200 dark:border-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                      data-accordion-target="#worldwide-shipping-body"
                      aria-expanded="true"
                      aria-controls="worldwide-shipping-body"
                    >
                      <span>Worldwide Shipping</span>
                      <svg
                        aria-hidden="true"
                        data-accordion-icon=""
                        class="w-5 h-5 rotate-180 shrink-0"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        />
                      </svg>
                    </button>
                  </h2>
                  <div
                    id="worldwide-shipping-body"
                    class="hidden"
                    aria-labelledby="worldwide-shipping-heading"
                  >
                    <div
                      class="py-2 space-y-2 font-light border-b border-gray-200 dark:border-gray-600"
                    >
                      <label class="relative flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          value=""
                          class="sr-only peer"
                          name="shipping"
                          checked=""
                        />
                        <div
                          class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
                        ></div>
                        <span
                          class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                          >North America</span
                        >
                      </label>
                      <label class="relative flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          value=""
                          class="sr-only peer"
                          name="shipping"
                        />
                        <div
                          class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
                        ></div>
                        <span
                          class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                          >South America</span
                        >
                      </label>
                      <label class="relative flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          value=""
                          class="sr-only peer"
                          name="shipping"
                        />
                        <div
                          class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
                        ></div>
                        <span
                          class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                          >Asia</span
                        >
                      </label>
                      <label class="relative flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          value=""
                          class="sr-only peer"
                          name="shipping"
                          checked=""
                        />
                        <div
                          class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
                        ></div>
                        <span
                          class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                          >Australia</span
                        >
                      </label>
                      <label class="relative flex items-center cursor-pointer">
                        <input
                          type="checkbox"
                          value=""
                          class="sr-only peer"
                          name="shipping"
                        />
                        <div
                          class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"
                        ></div>
                        <span
                          class="ml-3 text-sm font-medium text-gray-900 dark:text-gray-300"
                          >Europe</span
                        >
                      </label>
                    </div>
                  </div>
                  <!-- Rating -->
                  <h2 id="rating-heading">
                    <button
                      type="button"
                      class="flex items-center justify-between w-full py-2 px-1.5 text-sm font-medium text-left text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                      data-accordion-target="#rating-body"
                      aria-expanded="true"
                      aria-controls="rating-body"
                    >
                      <span>Rating</span>
                      <svg
                        aria-hidden="true"
                        data-accordion-icon=""
                        class="w-5 h-5 rotate-180 shrink-0"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          clip-rule="evenodd"
                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        />
                      </svg>
                    </button>
                  </h2>
                  <div id="rating-body" class="hidden" aria-labelledby="rating-heading">
                    <div
                      class="py-2 space-y-2 font-light border-b border-gray-200 dark:border-gray-600"
                    >
                      <div class="flex items-center">
                        <input
                          id="five-stars"
                          type="radio"
                          value=""
                          name="rating"
                          class="w-4 h-4 bg-gray-100 border-gray-300 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                        <label for="five-stars" class="flex items-center ml-2">
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>First star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Second star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Third star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fourth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fifth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="four-stars"
                          type="radio"
                          value=""
                          name="rating"
                          class="w-4 h-4 bg-gray-100 border-gray-300 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                        <label for="four-stars" class="flex items-center ml-2">
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>First star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Second star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Third star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fourth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fifth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="three-stars"
                          type="radio"
                          value=""
                          name="rating"
                          checked=""
                          class="w-4 h-4 bg-gray-100 border-gray-300 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                        <label for="three-stars" class="flex items-center ml-2">
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>First star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Second star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Third star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fourth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fifth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="two-stars"
                          type="radio"
                          value=""
                          name="rating"
                          class="w-4 h-4 bg-gray-100 border-gray-300 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                        <label for="two-stars" class="flex items-center ml-2">
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>First star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Second star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Third star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fourth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fifth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                        </label>
                      </div>
                      <div class="flex items-center">
                        <input
                          id="one-star"
                          type="radio"
                          value=""
                          name="rating"
                          class="w-4 h-4 bg-gray-100 border-gray-300 text-primary-600 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-700 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        />
                        <label for="one-star" class="flex items-center ml-2">
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-yellow-400"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>First star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Second star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Third star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fourth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                          <svg
                            aria-hidden="true"
                            class="w-5 h-5 text-gray-300 dark:text-gray-500"
                            fill="currentColor"
                            viewbox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg"
                          >
                            <title>Fifth star</title>
                            <path
                              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                            />
                          </svg>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center space-x-3 w-full md:w-auto">
                <button
                  id="actionsDropdownButton"
                  data-dropdown-toggle="actionsDropdown"
                  class="w-full md:w-auto flex items-center justify-center py-2 px-4 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg border border-gray-200 hover:bg-gray-100 hover:text-primary-700 focus:z-10 focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 dark:hover:text-white dark:hover:bg-gray-700"
                  type="button"
                >
                  Actions
                  <svg
                    class="-mr-1 ml-1.5 w-5 h-5"
                    fill="currentColor"
                    viewbox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      clip-rule="evenodd"
                      fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                    />
                  </svg>
                </button>
                <div
                  id="actionsDropdown"
                  class="hidden z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600"
                >
                  <ul
                    class="py-1 text-sm text-gray-700 dark:text-gray-200"
                    aria-labelledby="actionsDropdownButton"
                  >
                    <li>
                      <a
                        href="#"
                        class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white"
                        >Mass Edit</a
                      >
                    </li>
                  </ul>
                  <div class="py-1">
                    <a
                      href="#"
                      class="block py-2 px-4 text-sm text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white"
                      >Delete all</a
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
              <thead
                class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400"
              >
                <tr>
                  <th scope="col" class="p-4">
                    <div class="flex items-center">
                      <input
                        id="checkbox-all"
                        type="checkbox"
                        class="w-4 h-4 text-primary-600 bg-gray-100 rounded border-gray-300 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                      />
                      <label for="checkbox-all" class="sr-only">checkbox</label>
                    </div>
                  </th>
                  <th scope="col" class="p-4">Product</th>
                  <th scope="col" class="p-4">Brand</th>
                  <th scope="col" class="p-4">Category</th>
                  <th scope="col" class="p-4">View</th>
                  <th scope="col" class="p-4">Rating</th>
                  <th scope="col" class="p-4">Status</th>
                  <th scope="col" class="p-4">Option</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="product in products"
                  :key="product.id"
                  class="border-b dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700"
                >
                  <td class="p-4 w-4">
                    <div class="flex items-center">
                      <input
                        id="checkbox-table-search-1"
                        type="checkbox"
                        onclick="event.stopPropagation()"
                        class="w-4 h-4 text-primary-600 bg-gray-100 rounded border-gray-300 focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                      />
                      <label for="checkbox-table-search-1" class="sr-only"
                        >checkbox</label
                      >
                    </div>
                  </td>
                  <th
                    scope="row"
                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                  >
                    <div class="flex items-center mr-3">
                      <img
                        v-if="product.product_images && product.product_images.length > 0"
                        :src="
                          getImageUrl(
                            product.product_images[0].image_url ||
                              product.product_images[0].image
                          )
                        "
                        alt="Image"
                        class="h-8 w-auto mr-3"
                      />
                      <span> {{ product.name }}</span>
                    </div>
                  </th>
                  <!-- product_category -->
                  <td class="px-4 py-3">
                    <span
                      class="bg-primary-100 text-primary-800 text-xs font-medium px-2 py-0.5 rounded dark:bg-primary-900 dark:text-primary-300"
                    >
                      {{ product.brand ? product.brand.name : "N/A" }}
                    </span>
                  </td>
                  <!-- product stock -->
                  <td
                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                  >
                    <div class="flex items-center">
                      {{ product.category ? product.category.name : "N/A" }}
                    </div>
                  </td>
                  <td
                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                  >
                    {{
                      (product.views || []).reduce(
                        (sum, view) => sum + view.view_count,
                        0
                      )
                    }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center space-x-2">
                      <!-- Stars Display -->
                      <div class="flex items-center">
                        <!-- Full Stars -->
                        <span
                          v-for="n in renderStars(getAverageRating(product)).full"
                          :key="'full-' + n"
                          class="text-yellow-400 text-lg"
                        >
                          ★
                        </span>
                        <!-- Half Star -->
                        <span
                          v-if="renderStars(getAverageRating(product)).half"
                          class="text-yellow-400 text-lg"
                        >
                          ☆
                        </span>
                        <!-- Empty Stars -->
                        <span
                          v-for="n in renderStars(getAverageRating(product)).empty"
                          :key="'empty-' + n"
                          class="text-gray-300 text-lg"
                        >
                          ☆
                        </span>
                      </div>
                      <!-- Rating Number -->
                      <span class="text-sm text-gray-600 font-medium">
                        {{ getAverageRating(product) }}
                      </span>
                      <!-- Review Count -->
                      <span class="text-xs text-gray-500">
                        ({{ getReviewCount(product) }})
                      </span>
                    </div>
                  </td>
                  <td
                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                  >
                    {{ product.status }}
                  </td>
                  <td
                    class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap dark:text-white"
                  >
                    <button
                      :id="`${product.id}-button`"
                      :data-dropdown-toggle="`${product.id}`"
                      class="inline-flex items-center text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 p-1.5 dark:hover-bg-gray-800 text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-gray-100"
                      type="button"
                    >
                      <svg
                        class="w-5 h-5"
                        aria-hidden="true"
                        fill="currentColor"
                        viewbox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 12a2 2 0 100-4 2 2 0 000 4z"
                        />
                      </svg>
                    </button>
                    <div
                      :id="`${product.id}`"
                      class="hidden z-10 w-44 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600"
                    >
                      <ul class="py-1 text-sm" :aria-labelledby="`${product.id}-button`">
                        <li>
                          <button
                            @click="openEditProduct(product)"
                            class="flex w-full items-center py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-gray-700 dark:text-gray-200"
                          >
                            <svg
                              class="w-4 h-4 mr-2"
                              xmlns="http://www.w3.org/2000/svg"
                              viewbox="0 0 20 20"
                              fill="currentColor"
                              aria-hidden="true"
                            >
                              <path
                                d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z"
                              />
                              <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                              />
                            </svg>
                            Edit
                          </button>
                        </li>

                        <li>
                          <button
                            @click="openPreviewModal(product)"
                            type="button"
                            class="flex w-full items-center py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white text-gray-700 dark:text-gray-200"
                          >
                            <svg
                              class="w-4 h-4 mr-2"
                              xmlns="http://www.w3.org/2000/svg"
                              viewbox="0 0 20 20"
                              fill="currentColor"
                              aria-hidden="true"
                            >
                              <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                              <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                              />
                            </svg>
                            Preview
                          </button>
                        </li>
                        <li>
                          <button
                            @click="openDelete(product)"
                            type="button"
                            class="flex w-full items-center py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600 text-red-500 dark:hover:text-red-400"
                          >
                            <svg
                              class="w-4 h-4 mr-2"
                              viewbox="0 0 14 15"
                              fill="none"
                              xmlns="http://www.w3.org/2000/svg"
                              aria-hidden="true"
                            >
                              <path
                                fill-rule="evenodd"
                                clip-rule="evenodd"
                                fill="currentColor"
                                d="M6.09922 0.300781C5.93212 0.30087 5.76835 0.347476 5.62625 0.435378C5.48414 0.523281 5.36931 0.649009 5.29462 0.798481L4.64302 2.10078H1.59922C1.36052 2.10078 1.13161 2.1956 0.962823 2.36439C0.79404 2.53317 0.699219 2.76209 0.699219 3.00078C0.699219 3.23948 0.79404 3.46839 0.962823 3.63718C1.13161 3.80596 1.36052 3.90078 1.59922 3.90078V12.9008C1.59922 13.3782 1.78886 13.836 2.12643 14.1736C2.46399 14.5111 2.92183 14.7008 3.39922 14.7008H10.5992C11.0766 14.7008 11.5344 14.5111 11.872 14.1736C12.2096 13.836 12.3992 13.3782 12.3992 12.9008V3.90078C12.6379 3.90078 12.8668 3.80596 13.0356 3.63718C13.2044 3.46839 13.2992 3.23948 13.2992 3.00078C13.2992 2.76209 13.2044 2.53317 13.0356 2.36439C12.8668 2.1956 12.6379 2.10078 12.3992 2.10078H9.35542L8.70382 0.798481C8.62913 0.649009 8.5143 0.523281 8.37219 0.435378C8.23009 0.347476 8.06631 0.30087 7.89922 0.300781H6.09922ZM4.29922 5.70078C4.29922 5.46209 4.39404 5.23317 4.56282 5.06439C4.73161 4.8956 4.96052 4.80078 5.19922 4.80078C5.43791 4.80078 5.66683 4.8956 5.83561 5.06439C6.0044 5.23317 6.09922 5.46209 6.09922 5.70078V11.1008C6.09922 11.3395 6.0044 11.5684 5.83561 11.7372C5.66683 11.906 5.43791 12.0008 5.19922 12.0008C4.96052 12.0008 4.73161 11.906 4.56282 11.7372C4.39404 11.5684 4.29922 11.3395 4.29922 11.1008V5.70078ZM8.79922 4.80078C8.56052 4.80078 8.33161 4.8956 8.16282 5.06439C7.99404 5.23317 7.89922 5.46209 7.89922 5.70078V11.1008C7.89922 11.3395 7.99404 11.5684 8.16282 11.7372C8.33161 11.906 8.56052 12.0008 8.79922 12.0008C9.03791 12.0008 9.26683 11.906 9.43561 11.7372C9.6044 11.5684 9.69922 11.3395 9.69922 11.1008V5.70078C9.69922 5.46209 9.6044 5.23317 9.43561 5.06439C9.26683 4.8956 9.03791 4.80078 8.79922 4.80078Z"
                              />
                            </svg>
                            Delete
                          </button>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </section>

    <FormProducts
      v-if="showProductForm"
      :showModal="showProductForm"
      :productData="editingProductData"
      :isEditing="isEditingProduct"
      :categories="categories"
      :brands="brands"
      :attributes="attributes"
      @productSubmitted="handleProductSubmitted"
      @formClosed="handleFormClosed"
      @requestRefreshAttributes="handleRequestRefreshAttributes"
    />
  </div>
</template>

<style lang="scss" scoped></style>