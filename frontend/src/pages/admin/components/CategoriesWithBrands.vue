<script setup>
import { ref, computed, onMounted } from "vue";
import axiosAdmin from "../../../axiosAdmin.js";

// Category-related refs
const categories = ref([]);
const error = ref(null);
const parentId = ref(null);
const isCategoryModal = ref(false);
const isDeleteCategoryModal = ref(false);
const categoryToDelete = ref(null);
const showAddCategoryForm = ref(false);
const categoryLoading = ref(false);
const isEditingCategory = ref(false);
const categoryToEdit = ref(null);

// Brand-related refs
const brands = ref([]);
const isBrandModal = ref(false);
const isDeleteBrandModal = ref(false);
const brandToDelete = ref(null);
const brandLoading = ref(false);
const isEditingBrand = ref(false);
const brandToEdit = ref(null);

// Category form
const categoryForm = ref({
  name: "",
  parent_id: null,
  img_url: "",
});

// Brand form
const brandForm = ref({
  name: "",
  img_url: "",
});

// Reset category form
const resetCategoryForm = () => {
  categoryForm.value = {
    name: "",
    parent_id: parentId.value,
    img_url: "",
  };
  categoryPreviewImage.value = null;
  isEditingCategory.value = false;
  categoryToEdit.value = null;
};

// Reset brand form
const resetBrandForm = () => {
  brandForm.value = {
    name: "",
    img_url: "",
  };
  brandPreviewImage.value = null;
  isEditingBrand.value = false;
  brandToEdit.value = null;
};

// Navigate back to a breadcrumb level
const goBackToBreadcrumb = (crumbId) => {
  parentId.value = crumbId;
  showAddCategoryForm.value = false;
  categoryForm.value.parent_id = crumbId;
};

// Navigate to a category
const goToCategory = (category) => {
  parentId.value = category.id;
  showAddCategoryForm.value = false;
  categoryForm.value.parent_id = category.id;
};

// Build breadcrumbs
const breadcrumbs = computed(() => {
  const crumbs = [{ id: null, name: "All Categories" }];
  let currentId = parentId.value;
  const path = [];

  while (currentId) {
    const cat = categories.value.find((c) => c.id === currentId);
    if (cat) {
      path.unshift(cat);
      currentId = cat.parent_id;
    } else {
      currentId = null;
    }
  }
  return crumbs.concat(path);
});

// Get displayed categories based on parentId
const displayedCategories = computed(() => {
  return categories.value.filter((cat) => cat.parent_id === parentId.value);
});

// Get parent category name for form display
const currentParentForm = computed(() => {
  if (parentId.value) {
    const parent = categories.value.find((c) => c.id === parentId.value);
    return parent ? parent.name : "Unknown";
  }
  return "";
});

// Open category modal for adding or editing
const openCategoryModal = (category = null) => {
  if (category) {
    isEditingCategory.value = true;
    categoryToEdit.value = category;
    categoryForm.value = {
      name: category.name,
      parent_id: category.parent_id || null,
      img_url: category.img_url || "",
    };
    categoryPreviewImage.value = category.img_url ? getImageUrl(category.img_url) : null;
  } else {
    resetCategoryForm();
    isEditingCategory.value = false;
  }
  isCategoryModal.value = true;
};

// Close category modal
const closeCategoryModal = () => {
  isCategoryModal.value = false;
  resetCategoryForm();
};

// Open brand modal for adding or editing
const openBrandModal = (brand = null) => {
  if (brand) {
    isEditingBrand.value = true;
    brandToEdit.value = brand;
    brandForm.value = {
      name: brand.name,
      img_url: brand.img_url || "",
    };
    brandPreviewImage.value = brand.img_url ? getImageUrl(brand.img_url) : null;
  } else {
    resetBrandForm();
    isEditingBrand.value = false;
  }
  isBrandModal.value = true;
};

// Close brand modal
const closeBrandModal = () => {
  isBrandModal.value = false;
  resetBrandForm();
};

// Get image URL
const getImageUrl = (path) => `${axiosAdmin.defaults.baseURL}${path}`;

// Category preview image
const categoryPreviewImage = ref(null);

// Brand preview image
const brandPreviewImage = ref(null);

// Handle file change for category
const handleCategoryFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/svg+xml"];
  if (!allowedTypes.includes(file.type)) {
    alert("Please select a valid image file (JPEG, PNG, GIF, SVG)");
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    alert("File size must not exceed 5MB");
    return;
  }

  categoryForm.value.img_url = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    categoryPreviewImage.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

// Handle file change for brand
const handleBrandFileChange = (event) => {
  const file = event.target.files[0];
  if (!file) return;

  const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/svg+xml"];
  if (!allowedTypes.includes(file.type)) {
    alert("Please select a valid image file (JPEG, PNG, GIF, SVG)");
    return;
  }

  if (file.size > 5 * 1024 * 1024) {
    alert("File size must not exceed 5MB");
    return;
  }

  brandForm.value.img_url = file;
  const reader = new FileReader();
  reader.onload = (e) => {
    brandPreviewImage.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

// Remove category preview image
const removeCategoryImage = () => {
  categoryForm.value.img_url = "";
  categoryPreviewImage.value = null;
};

// Remove brand preview image
const removeBrandImage = () => {
  brandForm.value.img_url = "";
  brandPreviewImage.value = null;
};

// Add category
const addCategory = async () => {
  if (!categoryForm.value.name.trim()) {
    alert("Please enter a category name");
    return;
  }

  categoryLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("name", categoryForm.value.name.trim());
    formData.append("parent_id", categoryForm.value.parent_id || "");

    if (categoryForm.value.img_url instanceof File) {
      formData.append("img_url", categoryForm.value.img_url);
    }

    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.post("/api/categories", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    if (response.status === 201 || response.status === 200) {
      alert("Category added successfully!");
      await fetchCategories();
      closeCategoryModal();
    }
  } catch (err) {
    console.error("Error adding category:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while adding the category";
    alert(errorMessage);
  } finally {
    categoryLoading.value = false;
  }
};

// Update category
const updateCategory = async () => {
  if (!categoryForm.value.name.trim()) {
    alert("Please enter a category name");
    return;
  }

  categoryLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("name", categoryForm.value.name.trim());
    formData.append("parent_id", categoryForm.value.parent_id || "");
    formData.append("_method", "PUT");

    if (categoryForm.value.img_url instanceof File) {
      formData.append("img_url", categoryForm.value.img_url);
    } else if (!categoryForm.value.img_url) {
      formData.append("img_url", "");
    }

    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.post(`/api/categories/${categoryToEdit.value.id}`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    if (response.status === 200 || response.status === 201) {
      alert("Category updated successfully!");
      await fetchCategories();
      closeCategoryModal();
    }
  } catch (err) {
    console.error("Error updating category:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while updating the category";
    alert(errorMessage);
  } finally {
    categoryLoading.value = false;
  }
};

// Add brand
const addBrand = async () => {
  if (!brandForm.value.name.trim()) {
    alert("Please enter a brand name");
    return;
  }

  brandLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("name", brandForm.value.name.trim());

    if (brandForm.value.img_url instanceof File) {
      formData.append("img_url", brandForm.value.img_url);
    }

    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.post("/api/brands", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    if (response.status === 201 || response.status === 200) {
      alert("Brand added successfully!");
      await fetchBrands();
      closeBrandModal();
    }
  } catch (err) {
    console.error("Error adding brand:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while adding the brand";
    alert(errorMessage);
  } finally {
    brandLoading.value = false;
  }
};

// Update brand
const updateBrand = async () => {
  if (!brandForm.value.name.trim()) {
    alert("Please enter a brand name");
    return;
  }

  brandLoading.value = true;
  try {
    const formData = new FormData();
    formData.append("name", brandForm.value.name.trim());
    formData.append("_method", "PUT");

    if (brandForm.value.img_url instanceof File) {
      formData.append("img_url", brandForm.value.img_url);
    } else if (!brandForm.value.img_url) {
      formData.append("img_url", "");
    }

    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.post(`/api/brands/${brandToEdit.value.id}`, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });

    if (response.status === 200 || response.status === 201) {
      alert("Brand updated successfully!");
      await fetchBrands();
      closeBrandModal();
    }
  } catch (err) {
    console.error("Error updating brand:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while updating the brand";
    alert(errorMessage);
  } finally {
    brandLoading.value = false;
  }
};

// Open delete category modal
const openDeleteCategoryModal = (category) => {
  categoryToDelete.value = category;
  isDeleteCategoryModal.value = true;
};

// Close delete category modal
const closeDeleteCategoryModal = () => {
  isDeleteCategoryModal.value = false;
  categoryToDelete.value = null;
};

// Open delete brand modal
const openDeleteBrandModal = (brand) => {
  brandToDelete.value = brand;
  isDeleteBrandModal.value = true;
};

// Close delete brand modal
const closeDeleteBrandModal = () => {
  isDeleteBrandModal.value = false;
  brandToDelete.value = null;
};

// Delete category
const deleteCategory = async () => {
  if (!categoryToDelete.value) return;

  const hasChildren = categories.value.some((cat) => cat.parent_id === categoryToDelete.value.id);
  if (hasChildren) {
    alert("Cannot delete this category as it has subcategories. Please delete subcategories first.");
    closeDeleteCategoryModal();
    return;
  }

  categoryLoading.value = true;
  try {
    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.delete(`/api/categories/${categoryToDelete.value.id}`);

    if (response.status === 200 || response.status === 204) {
      alert("Category deleted successfully!");
      await fetchCategories();
      closeDeleteCategoryModal();
    }
  } catch (err) {
    console.error("Error deleting category:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while deleting the category";
    alert(errorMessage);
  } finally {
    categoryLoading.value = false;
  }
};

// Delete brand
const deleteBrand = async () => {
  if (!brandToDelete.value) return;

  brandLoading.value = true;
  try {
    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.delete(`/api/brands/${brandToDelete.value.id}`);

    if (response.status === 200 || response.status === 204) {
      alert("Brand deleted successfully!");
      await fetchBrands();
      closeDeleteBrandModal();
    }
  } catch (err) {
    console.error("Error deleting brand:", err);
    const errorMessage = err.response?.data?.message || "An error occurred while deleting the brand";
    alert(errorMessage);
  } finally {
    brandLoading.value = false;
  }
};

// Fetch categories
const fetchCategories = async () => {
  try {
    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.get("/api/categories");
    categories.value = response.data;
  } catch (err) {
    console.error("Error fetching categories:", err);
    error.value = "Unable to load categories. Please try again later.";
  }
};

// Fetch brands
const fetchBrands = async () => {
  try {
    await axiosAdmin.get("sanctum/csrf-cookie");
    const response = await axiosAdmin.get("/api/brands");
    brands.value = response.data;
  } catch (err) {
    console.error("Error fetching brands:", err);
    error.value = "Unable to load brands. Please try again later.";
  }
};

onMounted(async () => {
  await Promise.all([fetchCategories(), fetchBrands()]);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6 sm:p-8 font-sans antialiased text-gray-800">
    <div class="max-w-screen-3xl px-4 mx-auto lg:px-12 mb-4 w-full">
      <div class="relative bg-white shadow-md dark:bg-gray-800 sm:rounded-lg">
        <div class="flex flex-col items-center justify-between p-4 space-y-3 md:flex-row md:space-y-0 md:space-x-4">
          <div class="w-full md:w-1/2">
            <form class="flex items-center">
              <label for="simple-search" class="sr-only">Search</label>
              <div class="relative w-full">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <svg
                    aria-hidden="true"
                    class="w-5 h-5 text-gray-500 dark:text-gray-400"
                    fill="currentColor"
                    viewBox="0 0 20 20"
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
                  id="simple-search"
                  class="block w-full p-2 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Search"
                  required=""
                />
              </div>
            </form>
          </div>
          <div
            class="flex flex-col items-stretch justify-end flex-shrink-0 w-full space-y-2 md:w-auto md:flex-row md:space-y-0 md:items-center md:space-x-3"
          >
            <button
              @click.prevent="openCategoryModal()"
              type="button"
              class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800"
              :disabled="categoryLoading"
            >
              <svg
                class="h-4 w-4 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
              >
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                />
              </svg>
              {{ categoryLoading ? "Loading..." : "Add Category" }}
            </button>
            <button
              @click.prevent="openBrandModal()"
              type="button"
              class="flex items-center justify-center px-4 py-2 text-sm font-medium text-white rounded-lg bg-green-700 hover:bg-green-800 focus:ring-4 focus:ring-green-300 dark:bg-green-600 dark:hover:bg-green-700 focus:outline-none dark:focus:ring-green-800"
              :disabled="brandLoading"
            >
              <svg
                class="h-4 w-4 mr-2"
                fill="currentColor"
                viewBox="0 0 20 20"
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
              >
                <path
                  clip-rule="evenodd"
                  fill-rule="evenodd"
                  d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                />
              </svg>
              {{ brandLoading ? "Loading..." : "Add Brand" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Section -->
    <div class="max-w-screen-3xl px-4 mx-auto lg:px-12 w-full">
      <nav
        class="text-sm font-medium text-gray-500 mb-2 flex items-center bg-white p-3 rounded-lg shadow-sm border border-gray-100"
      >
        <ol class="list-none p-0 inline-flex flex-wrap items-center">
          <li v-for="(crumb, index) in breadcrumbs" :key="crumb.id" class="flex items-center">
            <template v-if="index < breadcrumbs.length - 1">
              <a
                @click="goBackToBreadcrumb(crumb.id)"
                class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer transition-colors duration-200"
              >
                {{ crumb.name }}
              </a>
              <span class="mx-2 text-gray-400">/</span>
            </template>
            <template v-else>
              <span class="text-gray-900">{{ crumb.name }}</span>
            </template>
          </li>
        </ol>
      </nav>

      <div class="bg-white shadow-xl rounded-lg p-6 border border-gray-200 mb-8">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
          {{ breadcrumbs[breadcrumbs.length - 1].name }}
          <span v-if="displayedCategories.length > 0" class="text-lg text-gray-500 font-normal">
            ({{ displayedCategories.length }} categories)
          </span>
        </h2>

        <div v-if="displayedCategories.length === 0" class="text-gray-600 italic text-center py-8">
          No categories available.
          <p v-if="!showAddCategoryForm" class="mt-2">
            Let's
            <button
              @click.prevent="openCategoryModal()"
              class="text-blue-600 hover:underline font-semibold"
            >
              Add a Category
            </button>
            !
          </p>
        </div>

        <div
          v-else
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
        >
          <div
            v-for="category in displayedCategories"
            :key="category.id"
            class="relative bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col items-center justify-center text-center cursor-pointer hover:shadow-lg hover:border-blue-300 transition duration-200 ease-in-out group"
            @click="goToCategory(category)"
          >
            <button
              @click.stop="openDeleteCategoryModal(category)"
              class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 hover:scale-110 z-10"
              title="Delete this category"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
            </button>
            <button
              @click.stop="openCategoryModal(category)"
              class="absolute top-2 left-2 p-1 bg-blue-500 text-white rounded-full opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 hover:scale-110 z-10"
              title="Edit this category"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                ></path>
              </svg>
            </button>

            <div class="flex flex-col items-center justify-center w-full h-full">
              <i
                v-if="category.icon"
                :class="[
                  category.icon,
                  'text-5xl text-gray-500 mb-3 group-hover:text-blue-600 transition-colors',
                ]"
              ></i>
              <img
                v-else-if="category.img_url"
                :src="getImageUrl(category.img_url)"
                :alt="category.name"
                class="w-20 h-20 object-contain mb-3 rounded-full border border-gray-200"
              />
              <div
                v-else
                class="w-20 h-20 flex items-center justify-center bg-gray-200 rounded-full mb-3 text-gray-500"
              >
                <i class="fa-solid fa-image text-3xl"></i>
              </div>
              <p class="text-base font-semibold text-gray-800 group-hover:text-blue-800">
                {{ category.name }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                <span
                  v-if="categories.filter((c) => c.parent_id === category.id).length > 0"
                >
                  ({{ categories.filter((c) => c.parent_id === category.id).length }} sub)
                </span>
                <span v-else> (0 sub) </span>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand Section -->
    <div class="max-w-screen-3xl px-4 mx-auto lg:px-12 w-full">
      <div class="bg-white shadow-xl rounded-lg p-6 border border-gray-200">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">
          All Brands
          <span v-if="brands.length > 0" class="text-lg text-gray-500 font-normal">
            ({{ brands.length }} brands)
          </span>
        </h2>

        <div v-if="brands.length === 0" class="text-gray-600 italic text-center py-8">
          No brands available.
          <p class="mt-2">
            Let's
            <button
              @click.prevent="openBrandModal()"
              class="text-green-600 hover:underline font-semibold"
            >
              Add a Brand
            </button>
            !
          </p>
        </div>

        <div
          v-else
          class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
        >
          <div
            v-for="brand in brands"
            :key="brand.id"
            class="relative bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col items-center justify-center text-center hover:shadow-lg hover:border-green-300 transition duration-200 ease-in-out group"
          >
            <button
              @click.stop="openDeleteBrandModal(brand)"
              class="absolute top-2 right-2 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 hover:scale-110 z-10"
              title="Delete this brand"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fill-rule="evenodd"
                  d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                  clip-rule="evenodd"
                ></path>
              </svg>
            </button>
            <button
              @click.stop="openBrandModal(brand)"
              class="absolute top-2 left-2 p-1 bg-green-500 text-white rounded-full opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity duration-200 hover:scale-110 z-10"
              title="Edit this brand"
            >
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path
                  d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                ></path>
              </svg>
            </button>

            <div class="flex flex-col items-center justify-center w-full h-full">
              <img
                v-if="brand.img_url"
                :src="getImageUrl(brand.img_url)"
                :alt="brand.name"
                class="w-20 h-20 object-contain mb-3 rounded-full border border-gray-200"
              />
              <div
                v-else
                class="w-20 h-20 flex items-center justify-center bg-gray-200 rounded-full mb-3 text-gray-500"
              >
                <i class="fa-solid fa-image text-3xl"></i>
              </div>
              <p class="text-base font-semibold text-gray-800 group-hover:text-green-800">
                {{ brand.name }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Category Add/Edit Modal -->
    <div
      v-if="isCategoryModal"
      tabindex="-1"
      @click.self="closeCategoryModal"
      class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] md:h-full"
    >
      <div class="relative p-4 w-full max-w-3xl h-full md:h-auto">
        <div
          class="relative p-4 md:mt-20 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5"
        >
          <div
            class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600"
          >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ isEditingCategory ? "Edit Category" : "Add Category" }}
            </h3>
            <button
              @click.prevent="closeCategoryModal"
              type="button"
              class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                aria-hidden="true"
                class="w-5 h-5"
                fill="currentColor"
                viewBox="0 0 20 20"
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
          </div>
          <form @submit.prevent="isEditingCategory ? updateCategory() : addCategory()">
            <div class="grid gap-4 mb-4 sm:grid-cols-2">
              <div>
                <label
                  for="category-name"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                >
                  Category Name *
                </label>
                <input
                  type="text"
                  name="category-name"
                  id="category-name"
                  v-model="categoryForm.name"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Type category name"
                  required
                />
              </div>
              <div>
                <label
                  for="parentCategory"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                >
                  Parent Category
                </label>
                <select
                  id="parentCategory"
                  v-model="categoryForm.parent_id"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                >
                  <option :value="null">-- Root Category --</option>
                  <optgroup
                    v-if="currentParentForm && parentId"
                    :label="`Current Parent (${currentParentForm})`"
                  >
                    <option :value="parentId">
                      {{ currentParentForm }}
                    </option>
                  </optgroup>
                  <optgroup label="Level 1 Categories">
                    <option
                      v-for="cat in categories.filter((c) => !c.parent_id)"
                      :key="cat.id"
                      :value="cat.id"
                    >
                      {{ cat.name }}
                    </option>
                  </optgroup>
                </select>
              </div>
            </div>

            <div class="mb-4 p-4">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Category Image</h3>
              <div v-if="categoryPreviewImage" class="mb-4">
                <div
                  class="relative bg-white rounded-lg shadow-md p-2 flex items-center justify-center border max-w-xs"
                >
                  <img
                    :src="categoryPreviewImage"
                    alt="Preview"
                    class="max-w-full max-h-32 object-contain"
                  />
                  <button
                    type="button"
                    @click="removeCategoryImage"
                    class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full text-xs"
                    title="Remove image"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 012 0v6a1 1 0 11-2 0V8z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </button>
                </div>
              </div>

              <div class="flex justify-center items-center w-full">
                <label
                  for="category-dropzone-file"
                  class="flex flex-col justify-center items-center w-full h-35 bg-gray-50 rounded-lg border-2 border-gray-300 border-dashed cursor-pointer dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                >
                  <div class="flex flex-col justify-center items-center pt-5 pb-6">
                    <svg
                      aria-hidden="true"
                      class="mb-3 w-10 h-10 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                      />
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                      <span class="font-semibold">Click to upload</span> or drag and drop
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      SVG, PNG, JPG or GIF (MAX. 5MB)
                    </p>
                  </div>
                  <input
                    id="category-dropzone-file"
                    type="file"
                    class="hidden"
                    @change="handleCategoryFileChange"
                    accept="image/svg+xml, image/png, image/jpeg, image/gif"
                  />
                </label>
              </div>
            </div>

            <div class="items-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
              <button
                type="submit"
                :disabled="categoryLoading"
                class="w-full sm:w-auto justify-center text-white inline-flex bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 disabled:opacity-50"
              >
                {{ categoryLoading ? (isEditingCategory ? "Updating..." : "Adding...") : (isEditingCategory ? "Update Category" : "Add Category") }}
              </button>
              <button
                @click.prevent="closeCategoryModal"
                type="button"
                class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
              >
                <svg
                  class="mr-1 -ml-1 w-5 h-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                  />
                </svg>
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Brand Add/Edit Modal -->
    <div
      v-if="isBrandModal"
      tabindex="-1"
      @click.self="closeBrandModal"
      class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] md:h-full"
    >
      <div class="relative p-4 w-full max-w-3xl h-full md:h-auto">
        <div
          class="relative p-4 md:mt-20 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5"
        >
          <div
            class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600"
          >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ isEditingBrand ? "Edit Brand" : "Add Brand" }}
            </h3>
            <button
              @click.prevent="closeBrandModal"
              type="button"
              class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
            >
              <svg
                aria-hidden="true"
                class="w-5 h-5"
                fill="currentColor"
                viewBox="0 0 20 20"
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
          </div>
          <form @submit.prevent="isEditingBrand ? updateBrand() : addBrand()">
            <div class="grid gap-4 mb-4 sm:grid-cols-1">
              <div>
                <label
                  for="brand-name"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                >
                  Brand Name *
                </label>
                <input
                  type="text"
                  name="brand-name"
                  id="brand-name"
                  v-model="brandForm.name"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Type brand name"
                  required
                />
              </div>
            </div>

            <div class="mb-4 p-4">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Brand Image</h3>
              <div v-if="brandPreviewImage" class="mb-4">
                <div
                  class="relative bg-white rounded-lg shadow-md p-2 flex items-center justify-center border max-w-xs"
                >
                  <img
                    :src="brandPreviewImage"
                    alt="Preview"
                    class="max-w-full max-h-32 object-contain"
                  />
                  <button
                    type="button"
                    @click="removeBrandImage"
                    class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full text-xs"
                    title="Remove image"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 012 0v6a1 1 0 11-2 0V8z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </button>
                </div>
              </div>

              <div class="flex justify-center items-center w-full">
                <label
                  for="brand-dropzone-file"
                  class="flex flex-col justify-center items-center w-full h-35 bg-gray-50 rounded-lg border-2 border-gray-300 border-dashed cursor-pointer dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                >
                  <div class="flex flex-col justify-center items-center pt-5 pb-6">
                    <svg
                      aria-hidden="true"
                      class="mb-3 w-10 h-10 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                      />
                    </svg>
                    <p class="mb-2 text-sm text-gray-500 dark:text-gray-400">
                      <span class="font-semibold">Click to upload</span> or drag and drop
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      SVG, PNG, JPG or GIF (MAX. 5MB)
                    </p>
                  </div>
                  <input
                    id="brand-dropzone-file"
                    type="file"
                    class="hidden"
                    @change="handleBrandFileChange"
                    accept="image/svg+xml, image/png, image/jpeg, image/gif"
                  />
                </label>
              </div>
            </div>

            <div class="items-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
              <button
                type="submit"
                :disabled="brandLoading"
                class="w-full sm:w-auto justify-center text-white inline-flex bg-green-700 hover:bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-green-600 dark:hover:bg-green-700 dark:focus:ring-green-800 disabled:opacity-50"
              >
                {{ brandLoading ? (isEditingBrand ? "Updating..." : "Adding...") : (isEditingBrand ? "Update Brand" : "Add Brand") }}
              </button>
              <button
                @click.prevent="closeBrandModal"
                type="button"
                class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
              >
                <svg
                  class="mr-1 -ml-1 w-5 h-5"
                  fill="currentColor"
                  viewBox="0 0 20 20"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    fill-rule="evenodd"
                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                    clip-rule="evenodd"
                  />
                </svg>
                Cancel
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Category Delete Modal -->
    <div
      v-if="isDeleteCategoryModal"
      tabindex="-1"
      @click.self="closeDeleteCategoryModal"
      class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] md:h-full"
    >
      <div class="relative p-4 w-full max-w-md h-full md:h-auto">
        <div class="relative p-4 text-center bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
          <button
            @click="closeDeleteCategoryModal"
            type="button"
            class="text-gray-400 absolute top-2.5 right-2.5 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg
              aria-hidden="true"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="sr-only">Close modal</span>
          </button>
          <svg
            class="text-gray-400 dark:text-gray-500 w-11 h-11 mb-3.5 mx-auto"
            aria-hidden="true"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 012 0v6a1 1 0 11-2 0V8z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <p class="mb-4 text-gray-500 dark:text-gray-300">
            Are you sure you want to delete the category
            <strong>"{{ categoryToDelete?.name }}"</strong>?
          </p>
          <p class="mb-4 text-sm text-red-600 dark:text-red-400">
            This action cannot be undone!
          </p>
          <div class="flex justify-center items-center space-x-4">
            <button
              @click="closeDeleteCategoryModal"
              type="button"
              class="py-2 px-3 text-sm font-medium text-gray-500 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
            >
              Cancel
            </button>
            <button
              @click="deleteCategory"
              :disabled="categoryLoading"
              type="button"
              class="py-2 px-3 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900 disabled:opacity-50"
            >
              {{ categoryLoading ? "Deleting..." : "Delete" }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand Delete Modal -->
    <div
      v-if="isDeleteBrandModal"
      tabindex="-1"
      @click.self="closeDeleteBrandModal"
      class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] md:h-full"
    >
      <div class="relative p-4 w-full max-w-md h-full md:h-auto">
        <div class="relative p-4 text-center bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5">
          <button
            @click="closeDeleteBrandModal"
            type="button"
            class="text-gray-400 absolute top-2.5 right-2.5 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
          >
            <svg
              aria-hidden="true"
              class="w-5 h-5"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                clip-rule="evenodd"
              ></path>
            </svg>
            <span class="sr-only">Close modal</span>
          </button>
          <svg
            class="text-gray-400 dark:text-gray-500 w-11 h-11 mb-3.5 mx-auto"
            aria-hidden="true"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <path
              fill-rule="evenodd"
              d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm6 0a1 1 0 012 0v6a1 1 0 11-2 0V8z"
              clip-rule="evenodd"
            ></path>
          </svg>
          <p class="mb-4 text-gray-500 dark:text-gray-300">
            Are you sure you want to delete the brand
            <strong>"{{ brandToDelete?.name }}"</strong>?
          </p>
          <p class="mb-4 text-sm text-red-600 dark:text-red-400">
            This action cannot be undone!
          </p>
          <div class="flex justify-center items-center space-x-4">
            <button
              @click="closeDeleteBrandModal"
              type="button"
              class="py-2 px-3 text-sm font-medium text-gray-500 bg-white rounded-lg border border-gray-200 hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
            >
              Cancel
            </button>
            <button
              @click="deleteBrand"
              :disabled="brandLoading"
              type="button"
              class="py-2 px-3 text-sm font-medium text-center text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-500 dark:hover:bg-red-600 dark:focus:ring-red-900 disabled:opacity-50"
            >
              {{ brandLoading ? "Deleting..." : "Delete" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

