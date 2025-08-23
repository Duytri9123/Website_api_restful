<script setup>
import { initFlowbite } from "flowbite";
import { onMounted } from "vue";

const props = defineProps({
  product: { type: Object, required: true },
});

const emit = defineEmits(["delete", "deleteClose"]);

const confirmDelete = () => {
  emit("delete", props.product.id);
  emit("deleteClose");
};
const cancelDelete = () => {
  emit("deleteClose");
};

onMounted(() => {
  initFlowbite();
});
</script>

<template>
  <div
    :id="`deleteModal-${product.id}`"
    class="fixed inset-0 flex items-center bg-gray-500/20 justify-center z-50"
  >
    <div class="bg-white p-6 rounded-lg shadow-lg w-96">
      <h2 class="text-lg font-bold mb-4">Xác nhận xoá</h2>
      <p class="mb-6">
        Bạn có chắc muốn xoá <b>{{ product.name }}</b> không?
      </p>
      <div class="flex justify-end space-x-3">
        <button
          @click="cancelDelete"
          class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400"
        >
          Huỷ
        </button>
        <button
          @click="confirmDelete"
          class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          Xoá
        </button>
      </div>
    </div>
  </div>
</template>
