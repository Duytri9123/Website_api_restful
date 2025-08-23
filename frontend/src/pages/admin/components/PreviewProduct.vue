<script setup>
import { initFlowbite } from "flowbite";
import { onMounted, reactive, ref, watch, computed, nextTick } from "vue";
import axiosAdmin from "../../../axiosAdmin.js";

const props = defineProps({
  productData: {
    type: Object,
    default: null,
  },
  showPreview: {
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
const emit = defineEmits(["PreviewClosed", "openEditFromPreview"]);
const error = ref(null);

const previewclosed = () => {
  emit("PreviewClosed");
  error.value = null;
};
const productImages = ref([]);
const selectedAttributes = reactive({});
const currentVariant = ref(null);

const form = ref({
  id: null,
  name: "",
  description: "",
  short_description: "",
  status: "active",
  attributes: [],
  category_id: "",
  brand_id: "",
  variants: [],
  images: [],
});

const getImageUrl = (path) => `${axiosAdmin.defaults.baseURL}/storage/${path}`;

const findVariant = () => {
  if (!form.value.variants || form.value.variants.length === 0) {
    currentVariant.value = null;
    return;
  }

  // Nếu không có thuộc tính nào được chọn, mặc định hiển thị biến thể đầu tiên (hoặc biến thể mặc định nếu có)
  if (Object.keys(selectedAttributes).length === 0) {
    currentVariant.value = form.value.variants.find(v => v.is_default) || form.value.variants[0];
    return;
  }

  currentVariant.value = form.value.variants.find((variant) => {
    // Đảm bảo variant.attribute_values là một mảng trước khi sử dụng .some()
    const variantAttributeValues = variant.attribute_values || [];

    // Kiểm tra xem tất cả các thuộc tính đã chọn có khớp với giá trị thuộc tính của biến thể này không
    return Object.entries(selectedAttributes).every(([attrId, attrValueId]) => {
      return variantAttributeValues.some(
        (av) => av.attribute && av.attribute.id == attrId && av.id == attrValueId
      );
    });
  });

  // Nếu không có kết quả khớp chính xác cho tổ hợp thuộc tính đã chọn,
  // bạn có thể chọn hiển thị một thông báo hoặc giữ biến thể hiện tại hoặc đặt null
  if (!currentVariant.value) {
    // Tùy chọn: Đặt lại về biến thể mặc định hoặc biến thể đầu tiên nếu không tìm thấy khớp
    currentVariant.value = form.value.variants.find(v => v.is_default) || form.value.variants[0];
  }
};


const selectAttribute = (attributeId, attributeValueId) => {
  selectedAttributes[attributeId] = attributeValueId;
  findVariant();
};
watch(
  () => props.productData,
  (newValue) => {
    if (newValue) {
      const variant = newValue.variants?.[0] ?? {}; // lấy biến thể mặc định
      Object.assign(form.value, {
        id: newValue.id,
        name: newValue.name,
        description: newValue.description,
        short_description: newValue.short_description,
        status: newValue.status,
        category_id: newValue.category?.id ?? "",
        brand_id: newValue.brand?.id ?? "",
        images: newValue.images || [],
        variants: newValue.variants || [],
      });
       const productAttributes = {};
      newValue.variants.forEach(variant => {
        const variantAttributeValues = variant.attribute_values || [];
        variantAttributeValues.forEach(av => {
          if (av.attribute) { 
            if (!productAttributes[av.attribute.id]) {
              productAttributes[av.attribute.id] = {
                id: av.attribute.id,
                name: av.attribute.name,
                values: []
              };
            }
            if (!productAttributes[av.attribute.id].values.some(v => v.id === av.id)) {
              productAttributes[av.attribute.id].values.push({
                id: av.id,
                value: av.value
              });
            }
          }
        });
      });
      form.value.attributes = Object.values(productAttributes);

      console.log("product attribute:", productAttributes);

      productImages.value = (newValue.images || []).map((img) => ({
        url: getImageUrl(img.url),
      }));

      if (newValue.variants && newValue.variants.length > 0) {
        currentVariant.value = newValue.variants[0];
        newValue.variants[0].attribute_values.forEach((av) => {
          selectedAttributes[av.attribute.id] = av.id;
        });
      } else {
        currentVariant.value = null;
      }
    }
  },
  { immediate: true }
);
watch(currentVariant, (newVariant) => {
  if (newVariant && newVariant.images && newVariant.images.length > 0) {
    productImages.value = newVariant.images.map((img) => ({
      url: getImageUrl(img.url),
    }));
  } else if (form.value.images) {
    productImages.value = form.value.images.map((img) => ({
      url: getImageUrl(img.url),
    }));
  } else {
    productImages.value = [];
  }
});

const openEditFromPreview = () => {
  emit("openEditFromPreview", form.value.id);
};
</script>
<template>
  <div>
    <!-- Preview Drawer -->
    <div
      v-if="props.showPreview"
      class="fixed inset-0 z-50 flex justify-center items-center"
      @click.self="previewclosed"
    >
      <div
        @click.self="previewclosed"
        :class="[
          'overflow-y-auto fixed top-16 left-0 z-40 p-4 w-full max-w-lg h-[calc(100%-4rem)] bg-white transition-transform dark:bg-gray-800',
          {
            '-translate-x-0': props.showPreview,
            '-translate-x-full': !props.showPreview,
          },
        ]"
        tabindex="-1"
      >
        <div>
          <h4
            class="mb-1.5 leading-none text-xl font-semibold text-gray-900 dark:text-white"
          >
            {{ form.name }}
          </h4>
          <h5
            v-if="currentVariant"
            class="mb-2 text-xl font-bold text-gray-900 dark:text-white line-through"
          >
            $ {{ currentVariant.original_price }}
          </h5>
          <h5
            v-if="currentVariant"
            class="mb-4 text-xl font-bold text-gray-900 dark:text-white"
          >
            $ {{ currentVariant.selling_price }}
          </h5>
        </div>

        <button
          @click.prevent="previewclosed"
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
        <dl class="sm:mb-5">
          <dt class="mb-2 font-semibold leading-none text-gray-900 dark:text-white">
            Details
          </dt>
          <dd class="mb-4 font-light text-gray-500 sm:mb-5 dark:text-gray-400">
            {{ form.short_description }}
          </dd>
        </dl>

        <div class="grid grid-cols-4 gap-4 mb-4 sm:mb-5">
          <div
            v-for="(img, index) in productImages"
            :key="index"
            class="p-2 w-auto bg-gray-100 rounded-lg dark:bg-gray-700"
          >
            <img :src="img.url" alt="image" />
          </div>
        </div>

        <div v-if="form.attributes.length > 0" class="mb-4">
          <div v-for="attribute in form.attributes" :key="attribute.id" class="mb-3">
            <h6 class="font-semibold text-gray-900 dark:text-white mb-2">
              {{ attribute.name }}:
            </h6>
            <div class="flex flex-wrap gap-2">
              <button
                v-for="value in attribute.values"
                :key="value.id"
                @click="selectAttribute(attribute.id, value.id)"
                :class="[
                  'px-3 py-1 text-sm rounded-lg border',
                  selectedAttributes[attribute.id] === value.id
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-gray-200 text-gray-800 border-gray-300 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600',
                ]"
              >
                {{ value.value }}
              </button>
            </div>
          </div>
        </div>
        <dl class="grid grid-cols-2 gap-4 mb-4">
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
              Brand
            </dt>
            <dd class="text-gray-500 dark:text-gray-400">
              {{ brands.find((brand) => brand.id === form.brand_id)?.name }}
            </dd>
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
  </div>
</template>

<style lang="scss" scoped></style>
