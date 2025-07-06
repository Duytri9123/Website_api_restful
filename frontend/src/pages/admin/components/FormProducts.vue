<script setup>
import { initFlowbite } from "flowbite";
import { onMounted, reactive, ref, watch, computed, nextTick } from "vue";
import axiosAdmin from "../../../axiosAdmin.js";

const props = defineProps({
  productData: {
    type: Object,
    default: null,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  showModal: {
    type: Boolean,
    default: false,
  },
  categories: {
    type: Array,
    default: () => [],
  },
  brands: {
    type: Array,
    default: () => [],
  },
  attributes: {
    type: Array,
    default: () => [],
  },
});

//emit
const emit = defineEmits(["productSubmitted", "formClosed", "requestRefreshAttributes"]);

// Fetch functions
const fetchAttributeValues = async (attributeId) => {
  if (!attributeId || attributeValuesMap[attributeId]) return;

  try {
    const response = await axiosAdmin.get(`/api/attributes/${attributeId}/values`);
    attributeValuesMap[attributeId] = response.data.data;
  } catch (err) {
    console.error(`Error Fetching values for Attribute ${attributeId}:`, err);
    error.value = `Failed to fetch attribute values. Please try again.`;
  }
};

// State
const attributeValuesMap = reactive({});
const error = ref(null);
const productImages = ref([]);
const variants = ref([]);

// Form data
const form = reactive({
  name: null,
  description: null,
  short_description: null,
  category_id: null,
  brand_id: null,
  status: "active",
  product_images: [],
  deleted_images: [],
});

// Modal states
const isAttributeModal = ref(false);
const isAttributeValueModal = ref(false);

// Attribute forms
const formAttribute = reactive({ name: null });

const formAttributeValue = reactive({
  value: null,
  attribute_id: null,
  code: null,
});

// Computed properties
const getImageUrl = (path) => `${axiosAdmin.defaults.baseURL}/storage/${path}`;

const getLeafCategories = computed(() => {
  const leafCategories = [];
  const findLeaves = (cats) => {
    cats.forEach((cat) => {
      if (cat.children && cat.children.length > 0) {
        // Nếu có con, tiếp tục đệ quy vào các danh mục con
        findLeaves(cat.children);
      } else {
        // Nếu không có con, đây là danh mục lá, thêm vào danh sách
        leafCategories.push(cat);
      }
    });
  };

  findLeaves(props.categories);
  return leafCategories;
});

const isImageTaken = computed(() => (imageIndex, currentVariantIndex) => {
  for (let i = 0; i < variants.value.length; i++) {
    if (i === currentVariantIndex || variants.value[i]._deleted) continue;

    if (
      variants.value[i].image_indexes &&
      variants.value[i].image_indexes.includes(imageIndex)
    ) {
      return true;
    }
  }
  return false;
});

const availableAttributes = computed(
  () => (currentAttributeId, variantIndex, currentGroupIndex) => {
    const usedAttributeIdsInThisVariant = new Set();
    const variant = variants.value[variantIndex];

    if (variant && variant.attribute_selection) {
      variant.attribute_selection.forEach((group, groupIdx) => {
        if (groupIdx !== currentGroupIndex && group.selectedAttributeId) {
          usedAttributeIdsInThisVariant.add(group.selectedAttributeId);
        }
      });
    }

    return props.attributes.filter(
      (attr) =>
        !usedAttributeIdsInThisVariant.has(attr.id) || attr.id === currentAttributeId
    );
  }
);

// Modal functions
const toggleAddAttribute = () => {
  isAttributeModal.value = !isAttributeModal.value;
};

const toggleAddAttributeValue = () => {
  isAttributeValueModal.value = !isAttributeValueModal.value;
  if (
    isAttributeValueModal.value &&
    props.attributes.length > 0 &&
    !formAttributeValue.attribute_id
  ) {
    formAttributeValue.attribute_id = props.attributes[0].id;
  }
  // Reset form attribute value khi đóng modal
  if (!isAttributeValueModal.value) {
    formAttributeValue.value = null;
    formAttributeValue.code = null;
    formAttributeValue.attribute_id = null;
  }
};

watch(
  () => props.productData,
  async (newProduct) => {
    if (newProduct) {
      // Khi có productData (editing)
      Object.assign(form, {
        name: newProduct.name,
        description: newProduct.description,
        short_description: newProduct.short_description,
        status: newProduct.status,
        product_images: [],
        deleted_images: [],
      });
      form.brand_id =
        newProduct.brand && newProduct.brand.id !== null
          ? Number(newProduct.brand.id)
          : null;
      form.category_id =
        newProduct.category && newProduct.category.id !== null
          ? Number(newProduct.category.id)
          : null;

      // Điền productImages từ dữ liệu có sẵn
      if (newProduct.images && Array.isArray(newProduct.images)) {
        productImages.value = newProduct.images.map((img) => ({
          id: img.id,
          url: getImageUrl(img.url),
          alt: img.alt || "",
          highlighted: img.highlighted || false,
          file: null, // Đánh dấu đây là ảnh đã có, không phải file mới upload
        }));
        console.log("Images assigned to productImages:", productImages.value);
      } else {
        productImages.value = []; // Nếu không có ảnh, đặt là mảng rỗng
        console.log("newProduct.images is empty or not an array."); // Thêm log này
      }

      variants.value = []; // Reset variants trước khi điền
      for (const variantData of newProduct.variants || []) {
        const newVariant = reactive({
          id: variantData.id,
          selling_price: variantData.selling_price,
          original_price: variantData.original_price,
          sku: variantData.sku,
          quantity: variantData.quantity,
          is_default: variantData.is_default ? "1" : "0",
          dimensions: variantData.dimensions,
          weight: variantData.weight,
          _deleted: false,
          image_indexes: variantData.image_indexes
            ? [...variantData.image_indexes].map(Number)
            : [],
          attribute_selection: [],
        });

        if (variantData.attribute_values && variantData.attribute_values.length > 0) {
          console.log("Processing attribute values:", variantData.attribute_values);
          console.log("Available attributes:", props.attributes);
          const attributeGroups = {};
          for (const av of variantData.attribute_values) {
            console.log(av);

            const foundAttributeId = av.attribute ? av.attribute.id : null;

            if (foundAttributeId) {
              if (!attributeGroups[foundAttributeId]) {
                attributeGroups[foundAttributeId] = reactive({
                  selectedAttributeId: foundAttributeId,
                  selectedAttributeValueIds: [],
                });
                // Vẫn fetch attribute values nếu chưa có trong map
                if (!attributeValuesMap[foundAttributeId]) {
                  await fetchAttributeValues(foundAttributeId);
                }
              }
              attributeGroups[foundAttributeId].selectedAttributeValueIds.push(av.id);
            } else {
              console.warn(
                `Không tìm thấy thuộc tính cho giá trị thuộc tính ID ${av.id}`
              );
            }
          }
          newVariant.attribute_selection = Object.values(attributeGroups);
        } else {
          newVariant.attribute_selection.push(
            reactive({ selectedAttributeId: null, selectedAttributeValueIds: [] })
          );
        }

        variants.value.push(newVariant);
      }
    } else {
      // Khi không có productData (chế độ thêm mới), reset form hoàn toàn
      Object.assign(form, {
        name: null,
        description: null,
        short_description: null,
        category_id: null,
        brand_id: null,
        status: "active",
        product_images: [],
        deleted_images: [],
      });
      productImages.value = [];
      variants.value = []; // Reset variants
      error.value = null;
    }
    // Đảm bảo Flowbite được init lại sau khi DOM có thể thay đổi
    nextTick(() => {
      initFlowbite();
    });
  },
  { immediate: true }
);

watch(
  () => props.showModal,
  (newVal) => {
    if (!newVal) {
      // Nếu modal đóng, reset form để chuẩn bị cho lần mở tiếp theo
      Object.assign(form, {
        name: null,
        description: null,
        short_description: null,
        category_id: null,
        brand_id: null,
        status: "active",
        product_images: [],
        deleted_images: [],
      });
      productImages.value = [];
      variants.value = [];
      error.value = null;
      // Đóng cả các modal con nếu chúng đang mở
      isAttributeModal.value = false;
      isAttributeValueModal.value = false;
    }
  }
);

const closeForm = () => {
  emit("formClosed");
  error.value = null;
};
// Variant functions
const toggleAddVariant = () => {
  const hasDefault = variants.value.some((v) => v.is_default === "1");
  variants.value.push({
    selling_price: null,
    original_price: null,
    sku: null,
    quantity: null,
    is_default: hasDefault ? "0" : "1",
    image_indexes: [],
    attribute_selection: [{ selectedAttributeId: null, selectedAttributeValueIds: [] }],
    dimensions: null,
    weight: null,
    id: null,
    _deleted: false,
  });
};

const addAttributeGroup = (variantIndex) => {
  if (!variants.value[variantIndex].attribute_selection) {
    variants.value[variantIndex].attribute_selection = [];
  }
  variants.value[variantIndex].attribute_selection.push(
    reactive({ selectedAttributeId: null, selectedAttributeValueIds: [] })
  );
};

const removeAttributeGroup = (variantIndex, attrGroupIndex) => {
  variants.value[variantIndex].attribute_selection.splice(attrGroupIndex, 1);
};

const toggleVariantMainImage = (variantIndex, imageIndexToToggle) => {
  const variant = variants.value[variantIndex];
  if (!variant.image_indexes) variant.image_indexes = [];

  const existingIndexPosition = variant.image_indexes.indexOf(imageIndexToToggle);

  if (existingIndexPosition > -1) {
    variant.image_indexes.splice(existingIndexPosition, 1);
  } else {
    if (isImageTaken.value(imageIndexToToggle, variantIndex)) {
      alert("Hình ảnh này đã được gán cho một biến thể khác.");
      return;
    }

    variant.image_indexes.push(imageIndexToToggle);
  }
};

const removeVariant = (index) => {
  if (confirm("Are you sure you want to remove this variant?")) {
    const variantToRemove = variants.value[index];
    if (variantToRemove.id) {
      variantToRemove._deleted = true;
    } else {
      variants.value.splice(index, 1);
    }
  }
};

// Image functions
const handleFileChange = (event) => {
  const files = Array.from(event.target.files);
  files.forEach((file) => {
    const reader = new FileReader();
    reader.onload = (e) => {
      productImages.value.push({
        url: e.target.result,
        alt: file.name,
        highlighted: false,
        file,
        id: null,
      });
      form.product_images.push(file);
    };
    reader.readAsDataURL(file);
  });
};

const removeImage = async (index) => {
  if (index >= 0 && index < productImages.value.length) {
    const image = productImages.value[index];
    if (image.id) form.deleted_images.push(image.id);

    if (image.file) {
      const fileIndex = form.product_images.findIndex((f) => f === image.file);
      if (fileIndex > -1) form.product_images.splice(fileIndex, 1);
    }

    productImages.value.splice(index, 1);

    variants.value.forEach((variant) => {
      if (!variant._deleted) {
        variant.image_indexes = variant.image_indexes.filter((idx) => idx !== index);
        variant.image_indexes = variant.image_indexes.map((idx) =>
          idx > index ? idx - 1 : idx
        );
      }
    });
  }
};

// Submit functions
const submitProduct = async () => {
  error.value = null;
  const formData = new FormData();

  // Basic product data
  formData.append("name", form.name || "");
  formData.append("description", form.description || "");
  formData.append("short_description", form.short_description || "");
  formData.append("brand_id", form.brand_id || "");
  formData.append("category_id", form.category_id || "");
  formData.append("status", form.status || "active");

  if (props.isEditing) formData.append("_method", "PUT");

  // Product images
  form.product_images.forEach((file, index) => {
    formData.append(`images[${index}]`, file);
  });

  form.deleted_images.forEach((id) => {
    formData.append(`deleted_images[]`, id);
  });

  // Variants
  const validVariants = variants.value.filter((v) => !v._deleted);
  if (validVariants.length === 0) {
    error.value = "Sản phẩm phải có ít nhất một biến thể.";
    return;
  }

  validVariants.forEach((variant, variantIndex) => {
    if (variant.id) formData.append(`variants[${variantIndex}][id]`, variant.id);

    formData.append(`variants[${variantIndex}][sku]`, variant.sku || "");
    formData.append(
      `variants[${variantIndex}][selling_price]`,
      variant.selling_price || 0
    );

    if (
      variant.original_price !== null &&
      variant.original_price !== undefined &&
      variant.original_price !== ""
    ) {
      formData.append(
        `variants[${variantIndex}][original_price]`,
        variant.original_price
      );
    }

    formData.append(`variants[${variantIndex}][quantity]`, variant.quantity || 0);
    formData.append(
      `variants[${variantIndex}][is_default]`,
      variant.is_default === "1" ? 1 : 0
    );

    if (
      variant.dimensions !== null &&
      variant.dimensions !== undefined &&
      variant.dimensions !== ""
    ) {
      formData.append(`variants[${variantIndex}][dimensions]`, variant.dimensions);
    }

    if (
      variant.weight !== null &&
      variant.weight !== undefined &&
      variant.weight !== ""
    ) {
      formData.append(`variants[${variantIndex}][weight]`, variant.weight);
    }

    // Image indexes
    if (variant.image_indexes && variant.image_indexes.length > 0) {
      variant.image_indexes.forEach((imgIndex, i) => {
        formData.append(`variants[${variantIndex}][image_indexes][${i}]`, imgIndex);
      });
    } else {
      formData.append(`variants[${variantIndex}][image_indexes][]`, "");
    }

    // Attribute values
    let combinedAttributeValueIds = [];
    if (variant.attribute_selection && variant.attribute_selection.length > 0) {
      variant.attribute_selection.forEach((attrGroup) => {
        if (
          attrGroup.selectedAttributeId &&
          attrGroup.selectedAttributeValueIds &&
          attrGroup.selectedAttributeValueIds.length > 0
        ) {
          combinedAttributeValueIds = combinedAttributeValueIds.concat(
            attrGroup.selectedAttributeValueIds
          );
        }
      });
    }

    combinedAttributeValueIds = [...new Set(combinedAttributeValueIds)];
    if (combinedAttributeValueIds.length > 0) {
      combinedAttributeValueIds.forEach((valueId) => {
        formData.append(`variants[${variantIndex}][attribute_value_ids][]`, valueId);
      });
    } else {
      formData.append(`variants[${variantIndex}][attribute_value_ids][]`, "");
    }
  });

  // Deleted variants
  const deletedVariantIds = variants.value
    .filter((v) => v._deleted && v.id)
    .map((v) => v.id);
  deletedVariantIds.forEach((id) => {
    formData.append(`deleted_variant_ids[]`, id);
  });

  // Debug log
  for (let pair of formData.entries()) {
    console.log(pair[0] + ": " + pair[1]);
  }
  try {
    let response;
    if (props.isEditing) {
      response = await axiosAdmin.post(
        `/api/products/${props.productData.id}`, // Sử dụng props.productData.id
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
        }
      );
      alert("Sản phẩm đã được cập nhật thành công!");
    } else {
      response = await axiosAdmin.post("/api/products", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });
      alert("Sản phẩm đã được thêm thành công!");
    }

    console.log("Phản hồi từ server:", response.data);
    closeForm();
    emit("productSubmitted");
  } catch (err) {
    console.error("Lỗi khi gửi sản phẩm:", err.response ? err.response.data : err);
    error.value = "Không thể gửi sản phẩm. Vui lòng thử lại.";
    if (err.response && err.response.data && err.response.data.errors) {
      for (const key in err.response.data.errors) {
        error.value += `\n- ${err.response.data.errors[key][0]}`;
      }
    }
  }
};

const addAttribute = async () => {
  if (!formAttribute.name) {
    error.value = "Attribute name is required.";
    return;
  }
  try {
    const response = await axiosAdmin.post("/api/attributes", formAttribute);

    alert("Attribute added successfully!");
    formAttribute.name = null;
    isAttributeModal.value = false;
    emit("requestRefreshAttributes");
  } catch (err) {
    console.error("Error adding attribute:", err);
    error.value = "Failed to add attribute. Please try again.";
  }
};
const addAttributeValue = async () => {
  if (
    !formAttributeValue.value ||
    !formAttributeValue.code ||
    !formAttributeValue.attribute_id
  ) {
    error.value = "Attribute value, code, and attribute selection are required.";
    return;
  }
  try {
    const response = await axiosAdmin.post(
      "/api/attributes/" + formAttributeValue.attribute_id + "/values",
      formAttributeValue
    );

    alert("Attribute value added successfully!");
    formAttributeValue.value = null;
    formAttributeValue.code = null;
    isAttributeValueModal.value = false;
    emit("requestRefreshAttributes");
  } catch (err) {
    console.error("Error adding attribute value:", err);
    error.value = "Failed to add attribute value. Please try again.";
  }
};


onMounted(async () => {});
</script>
<template>
  <div>
    <div
      v-if="props.showModal"
      tabindex="-1"
      @click.self="closeForm"
      class="flex overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 md:h-full"
    >
      <div
        class="relative p-4 w-full max-w-3xl md:h-auto max-h-[calc(100vh-4rem)] overflow-y-auto"
      >
        <div
          class="relative p-4 md:mt-20 bg-white rounded-lg shadow dark:bg-gray-800 sm:p-5"
        >
          <div
            class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5 dark:border-gray-600"
          >
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              {{ props.isEditing ? "Update Product" : "Add Product" }}
            </h3>
            <button
              @click.prevent="closeForm"
              type="button"
              class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white"
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
          </div>

          <form @submit.prevent="submitProduct">
            <div class="grid gap-4 mb-4 sm:grid-cols-1">
              <div>
                <label
                  for="name"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Product Name</label
                >
                <input
                  type="text"
                  name="name"
                  id="name"
                  v-model="form.name"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Type product name"
                  required
                />
              </div>
            </div>
            <div class="grid gap-4 mb-4 grid-cols-2 sm:grid-cols-2">
              <div>
                <label
                  for="brand"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Brand</label
                >
                <select
                  v-model="form.brand_id"
                  id="brand"
                  required
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                >
                  <option disabled :value="null">Select brand</option>
                  <option v-for="brand in props.brands" :key="brand.id" :value="brand.id">
                    {{ brand.name }}
                  </option>
                </select>
              </div>
              <div>
                <label
                  for="category"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Category</label
                >
                <select
                  v-model="form.category_id"
                  id="category"
                  class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  required
                >
                  <option disabled :value="null">Select category</option>
                  <option
                    v-for="category in getLeafCategories"
                    :key="category.id"
                    :value="category.id"
                  >
                    {{ category.name }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid gap-4 mb-4 grid-cols-2 sm:grid-cols-2">
              <div class="sm:col-span-1">
                <label
                  for="short_description"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Short Description</label
                >
                <textarea
                  v-model="form.short_description"
                  id="short_description"
                  rows="4"
                  class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Write short product description here"
                ></textarea>
              </div>

              <div class="sm:col-span-1">
                <label
                  for="description"
                  class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Description</label
                >
                <textarea
                  v-model="form.description"
                  id="description"
                  rows="4"
                  class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                  placeholder="Write full product description here"
                ></textarea>
              </div>
            </div>

            <div class="grid gap-4 mb-4 grid-cols-2 sm:grid-cols-1">
              <div class="space-y-4 sm:flex sm:space-y-0">
                <div class="flex items-center mr-4">
                  <input
                    checked
                    id="status-active"
                    type="radio"
                    value="active"
                    v-model="form.status"
                    name="productStatus"
                    class="w-4 h-4 bg-gray-100 rounded border-gray-300 text-primary-600 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <label
                    for="status-active"
                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Active</label
                  >
                </div>
                <div class="flex items-center mr-4">
                  <input
                    id="status-out-of-stock"
                    type="radio"
                    value="out_of_stock"
                    v-model="form.status"
                    name="productStatus"
                    class="w-4 h-4 bg-gray-100 rounded border-gray-300 text-primary-600 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <label
                    for="status-out-of-stock"
                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Out of Stock</label
                  >
                </div>
                <div class="flex items-center mr-4">
                  <input
                    id="status-inactive"
                    type="radio"
                    value="inactive"
                    v-model="form.status"
                    name="productStatus"
                    class="w-4 h-4 bg-gray-100 rounded border-gray-300 text-primary-600 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <label
                    for="status-inactive"
                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Inactive</label
                  >
                </div>
                <div class="flex items-center mr-4">
                  <input
                    id="status-discontinued"
                    type="radio"
                    value="discontinued"
                    v-model="form.status"
                    name="productStatus"
                    class="w-4 h-4 bg-gray-100 rounded border-gray-300 text-primary-600 dark:bg-gray-700 dark:border-gray-600"
                  />
                  <label
                    for="status-discontinued"
                    class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300"
                    >Discontinued</label
                  >
                </div>
              </div>
            </div>

            <div class="mb-4">
              <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                >Product Images</span
              >
              <div
                class="grid grid-cols-3 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-6 gap-4 mb-6"
              >
                <div
                  v-for="(img, index) in productImages"
                  :key="index"
                  class="relative bg-white rounded-lg shadow-md p-2 flex items-center justify-center border"
                  :class="{ 'border-orange-400 border-2': img.highlighted }"
                >
                  <img
                    :src="img.url"
                    alt="image"
                    class="max-w-full h-auto object-contain"
                  />

                  <button
                    type="button"
                    @click="removeImage(index)"
                    class="absolute -bottom-2 -left-2 bg-red-500 hover:bg-red-600 text-white p-1 rounded-full text-xs"
                    :class="{ 'bg-red-600': img.highlighted }"
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

              <div class="flex items-center justify-center w-full">
                <label
                  for="dropzone-file"
                  class="flex flex-col items-center justify-center w-full h-30 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-bray-800 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600"
                >
                  <div class="flex flex-col items-center justify-center pt-5 pb-6">
                    <svg
                      aria-hidden="true"
                      class="w-10 h-10 mb-3 text-gray-400"
                      fill="none"
                      stroke="currentColor"
                      viewbox="0 0 24 24"
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
                      <span class="font-semibold">Click to upload</span>
                      or drag and drop
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                      SVG, PNG, JPG or GIF (MAX. 800x400px)
                    </p>
                  </div>
                  <input
                    id="dropzone-file"
                    type="file"
                    class="hidden"
                    multiple
                    @change="handleFileChange"
                    accept="image/svg+xml, image/png, image/jpeg, image/gif"
                  />
                </label>
              </div>
            </div>

            <div class="grid gap-4 mb-4 grid-cols-1 sm:grid-cols-2">
              <!-- Add Variant Section -->
              <div>
                <div class="flex justify-between items-center mb-4">
                  <label
                    for="AddVariant"
                    class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                    >Add Variant</label
                  >
                  <button
                    type="button"
                    @click="toggleAddVariant"
                    class="p-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                    title="Add new variant"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                      xmlns="http://www.w3.org/2000/svg"
                    >
                      <path
                        fill-rule="evenodd"
                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                        clip-rule="evenodd"
                      ></path>
                    </svg>
                  </button>
                </div>
              </div>
              <!-- Add Attribute Section -->
              <div class="grid gap-4 mb-4 grid-cols-1 sm:grid-cols-2">
                <div>
                  <div class="flex justify-between items-center mb-4">
                    <label
                      for="AddAttribute"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Attribute</label
                    >
                    <button
                      type="button"
                      @click="toggleAddAttribute"
                      class="p-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                      title="Add new attribute"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </button>
                  </div>
                  <div
                    v-show="isAttributeModal"
                    class="mb-6 p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700"
                  >
                    <div class="grid gap-4 sm:grid-cols-1 mb-4">
                      <div>
                        <form @submit.prevent="addAttribute">
                          <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                            >Attribute Name</label
                          >
                          <input
                            type="text"
                            v-model="formAttribute.name"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            placeholder="attribute name"
                            required
                          />

                          <button
                            type="submit"
                            class="mt-2 px-3 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300"
                          >
                            Add Attribute
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <div>
                  <div class="flex justify-between items-center mb-4">
                    <label
                      for="AddAttribute"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Attribute Value</label
                    >
                    <button
                      type="button"
                      @click="toggleAddAttributeValue"
                      class="p-2.5 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                      title="Add new attribute"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </button>
                  </div>

                  <div
                    v-show="isAttributeValueModal"
                    class="mb-6 p-4 border rounded-lg bg-white dark:bg-gray-800 dark:border-gray-700"
                  >
                    <div class="grid gap-4 sm:grid-cols-1 mb-4">
                      <div>
                        <form @submit.prevent="addAttributeValue">
                          <label
                            class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                            >Select Attribute</label
                          >
                          <select
                            v-model="formAttributeValue.attribute_id"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                            required
                          >
                            <option
                              v-for="attribute in attributes"
                              :key="attribute.id"
                              :value="attribute.id"
                            >
                              {{ attribute.name }}
                            </option>
                          </select>
                          <div>
                            <label
                              class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                              >Attribute Value</label
                            >
                            <input
                              type="text"
                              v-model="formAttributeValue.value"
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                              placeholder="attribute value"
                              required
                            />
                          </div>
                          <div class="mt-2">
                            <label
                              class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                              >Code</label
                            >
                            <input
                              type="text"
                              v-model="formAttributeValue.code"
                              class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                              placeholder="attribute code"
                              required
                            />
                          </div>

                          <button
                            type="submit"
                            class="mt-2 px-3 py-2 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:outline-none focus:ring-red-300"
                          >
                            Add Value
                          </button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-for="(variant, index) in variants"
              :key="index"
              class="border bg-pink-50 rounded-lg p-4 grid gap-4 mb-4 sm:grid-cols-1"
            >
              <div class="flex justify-between items-center">
                <h5 class="text-lg font-semibold text-gray-900 dark:text-white">
                  Variant {{ index + 1 }}
                </h5>
                <button
                  type="button"
                  @click="removeVariant(index)"
                  class="p-2.5 text-sm font-medium text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                  title="Remove variant"
                >
                  <svg
                    class="w-4 h-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      fill-rule="evenodd"
                      d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1v-4a1 1 0 00-1-1H8z"
                      clip-rule="evenodd"
                    ></path>
                  </svg>
                </button>
              </div>

              <div class="grid gap-4 mb-4 sm:grid-cols-2">
                <div class="grid gap-4 mb-4 sm:grid-cols-2">
                  <div>
                    <label
                      :for="'variant_sku_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Variant Sku</label
                    >
                    <input
                      type="text"
                      v-model="variant.sku"
                      :id="'variant_sku_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Type variant sku"
                    />
                  </div>
                  <div>
                    <label
                      :for="'variant_is_default_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Is Default</label
                    >
                    <select
                      v-model="variant.is_default"
                      :id="'variant_is_default_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                    >
                      <option value="1">Yes</option>
                      <option value="0">No</option>
                    </select>
                  </div>
                </div>

                <div class="grid gap-4 mb-4 sm:grid-cols-2">
                  <div>
                    <label
                      :for="'variant_selling_price_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Selling Price</label
                    >
                    <input
                      type="number"
                      v-model="variant.selling_price"
                      :id="'variant_selling_price_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Selling price"
                      required
                    />
                  </div>
                  <div>
                    <label
                      :for="'variant_original_price_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Original Price</label
                    >
                    <input
                      type="number"
                      v-model="variant.original_price"
                      :id="'variant_original_price_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Original price"
                      required
                    />
                  </div>
                </div>
                <div class="grid gap-4 mb-4 sm:grid-cols-3">
                  <div>
                    <label
                      :for="'variant_quantity_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Quantity</label
                    >
                    <input
                      type="number"
                      v-model="variant.quantity"
                      :id="'variant_quantity_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Quantity"
                      required
                    />
                  </div>
                  <div>
                    <label
                      :for="'variant_dimensions_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Dimensions</label
                    >
                    <input
                      type="text"
                      v-model="variant.dimensions"
                      :id="'variant_dimensions_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Dimensions"
                    />
                  </div>
                  <div>
                    <label
                      :for="'variant_weight_' + index"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Weight</label
                    >
                    <input
                      type="number"
                      v-model="variant.weight"
                      :id="'variant_weight_' + index"
                      class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      placeholder="Weight"
                    />
                  </div>
                </div>
                <div class="grid gap-4 mb-4 sm:grid-cols-1">
                  <div class="flex justify-between">
                    <label
                      for="AddAttribute"
                      class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                      >Attribute:</label
                    >
                    <button
                      type="button"
                      @click="addAttributeGroup(index)"
                      class="p-2 text-sm font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                      title="Add new attribute"
                    >
                      <svg
                        class="w-4 h-4"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg"
                      >
                        <path
                          fill-rule="evenodd"
                          d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </button>
                  </div>

                  <div
                    v-for="(attrGroup, attrGroupIndex) in variant.attribute_selection"
                    :key="'variant-' + index + '-group-' + attrGroupIndex"
                    class="border rounded-lg p-2 grid gap-4 mb-4 sm:grid-cols-7 bg-yellow-50 dark:bg-yellow-900"
                  >
                    <div class="sm:col-span-3">
                      <label
                        :for="`variant_attribute_${index}_${attrGroupIndex}`"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                        >Attribute</label
                      >
                      <select
                        v-model="attrGroup.selectedAttributeId"
                        @change="
                          attrGroup.selectedAttributeValueIds = []; // Đặt lại các giá trị đã chọn
                          fetchAttributeValues(attrGroup.selectedAttributeId);
                        "
                        :id="`variant_attribute_${index}_${attrGroupIndex}`"
                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                      >
                        <option :value="null" disabled>Chọn một thuộc tính</option>
                        <option
                          v-for="attribute in availableAttributes(
                            attrGroup.selectedAttributeId,
                            index,
                            attrGroupIndex
                          )"
                          :key="attribute.id"
                          :value="attribute.id"
                        >
                          {{ attribute.name }}
                        </option>
                      </select>
                    </div>
                    <div class="sm:col-span-3">
                      <label
                        :for="`variant_attribute_value_${index}_${attrGroupIndex}`"
                        class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                        >Attribute value</label
                      >
                      <select
                        v-model="attrGroup.selectedAttributeValueIds"
                        :id="`variant_attribute_value_${index}_${attrGroupIndex}`"
                        class="bg-gray-50 border h-22 border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500"
                        multiple
                        :disabled="
                          !attrGroup.selectedAttributeId ||
                          !attributeValuesMap[attrGroup.selectedAttributeId] ||
                          attributeValuesMap[attrGroup.selectedAttributeId].length === 0
                        "
                      >
                        <option
                          v-for="value in attributeValuesMap[
                            attrGroup.selectedAttributeId
                          ]"
                          :key="value.id"
                          :value="value.id"
                        >
                          {{ value.value }}
                        </option>
                      </select>
                    </div>
                    <div class="flex items-center justify-end sm:col-span-1">
                      <button
                        type="button"
                        @click="removeAttributeGroup(index, attrGroupIndex)"
                        class="p-2.5 text-sm font-medium text-white bg-red-700 rounded-lg hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800"
                        title="Remove variant"
                      >
                        <svg
                          class="w-2 h-2"
                          fill="currentColor"
                          viewBox="0 0 20 20"
                          xmlns="http://www.w3.org/2000/svg"
                        >
                          <path
                            fill-rule="evenodd"
                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 011-1h4a1 1 0 110 2H8a1 1 0 01-1-1zm1 3a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 001-1v-4a1 1 0 00-1-1H8z"
                            clip-rule="evenodd"
                          ></path>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="mb-4 mt-4">
                <span class="block mb-2 text-sm font-medium text-gray-900 dark:text-white"
                  >Gắn hình ảnh sản phẩm chính (Tùy chọn)</span
                >
                <div class="grid grid-cols-4 gap-2">
                  <div
                    v-for="(productImg, prodImgIndex) in productImages"
                    :key="'prod-img-' + index + '-' + prodImgIndex"
                    @click="toggleVariantMainImage(index, prodImgIndex)"
                    class="relative p-1 border rounded-lg cursor-pointer flex justify-center items-center h-24 overflow-hidden"
                    :class="{
                      'border-blue-500 border-2': variant.image_indexes.includes(
                        prodImgIndex
                      ),
                      'opacity-50 cursor-not-allowed': isImageTaken(prodImgIndex, index),
                    }"
                    :title="
                      isImageTaken(prodImgIndex, index)
                        ? 'Hình ảnh này đã được gán cho một biến thể khác.'
                        : 'Gắn/Bỏ gắn hình ảnh này vào biến thể'
                    "
                  >
                    <img
                      :src="productImg.url"
                      class="max-w-full max-h-full object-contain"
                    />
                    <div
                      v-if="variant.image_indexes.includes(prodImgIndex)"
                      class="absolute top-1 right-1 text-blue-500"
                    >
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path
                          fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"
                        ></path>
                      </svg>
                    </div>
                  </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                  Chọn hình ảnh từ danh sách hình ảnh sản phẩm chính để liên kết với biến
                  thể này. Một hình ảnh chỉ có thể được liên kết với một biến thể.
                </p>
              </div>
            </div>

            <div class="items-center space-y-4 sm:flex sm:space-y-0 sm:space-x-4">
              <button
                type="submit"
                class="w-full sm:w-auto justify-center text-white inline-flex bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
              >
                {{ props.isEditing ? "Update Product" : "Add Product" }}
              </button>
              <button
                @click.prevent="closeForm"
                type="button"
                class="w-full justify-center sm:w-auto text-gray-500 inline-flex items-center bg-white hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-primary-300 rounded-lg border border-gray-200 text-sm font-medium px-5 py-2.5 hover:text-gray-900 focus:z-10 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-500 dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-gray-600"
              >
                <svg
                  class="mr-1 -ml-1 w-5 h-5"
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
                Discard
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
