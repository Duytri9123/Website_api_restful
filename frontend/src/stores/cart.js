import { defineStore } from "pinia";
export const useCartStore = defineStore("cart", {
  state: () => ({
    items: [],
    count: 0,
  }),

  getters: {
    cartItems: (state) => state.items || [],
    totalItems: (state) => state.count,
    totalPrice: (state) =>
      state.items.reduce(
        (total, item) => total + item.variant.selling_price * item.quantity,
        0
      ),
  },

  actions: {
    addToCart(product, variant, quantity = 1) {
      const existingItem = this.items.find(
        (item) => item?.variant?.id === variant?.id
      );

      if (existingItem) {
        existingItem.quantity += quantity;
      } else {
        this.items.push({
          productId: product.id,
          name: product.name,
          quantity,
          variant,
        });
      }

      this.updateCount();
      this.saveToLocalStorage();
    },

    removeFromCart(variantId) {
      this.items = this.items.filter((item) => item.variant.id !== variantId);
      this.updateCount();
      this.saveToLocalStorage();
    },

    updateQuantity(variantId, quantity) {
      const item = this.items.find((i) => i.variant.id === variantId);
      if (item) {
        item.quantity = quantity > 0 ? quantity : 1;
      }
      this.updateCount();
      this.saveToLocalStorage();
    },

    updateVariant(variantId, newVariant) {
      const item = this.items.find((i) => i.variant.id === variantId);
      if (item && newVariant) {
        item.variant = newVariant;
      }
      this.saveToLocalStorage();
    },

    clearCart() {
      this.items = [];
      this.count = 0;
      this.saveToLocalStorage();
    },

    updateCount() {
      this.count = this.items.reduce((total, item) => total + item.quantity, 0);
    },

    saveToLocalStorage() {
      if (typeof window !== "undefined") {
        localStorage.setItem(
          "cart",
          JSON.stringify({
            items: this.items,
            count: this.count,
          })
        );
      }
    },

    loadFromLocalStorage() {
      if (typeof window !== "undefined") {
        const saved = localStorage.getItem("cart");
        if (saved) {
          const data = JSON.parse(saved);
          this.items = data.items || [];
          this.count = data.count || 0;
        }
      }
    },
  },
});

export default useCartStore;
