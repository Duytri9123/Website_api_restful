import { defineStore } from "pinia";

export const useCartStore = defineStore("cart", {
  state: () => ({
    items: [],
    count: 0,
  }),

  getters: {
    totalItems: (state) => state.count,
    cartItems: (state) => state.items,
    totalPrice: (state) => {
      return state.items.reduce(
        (total, item) => total + item.price * item.quantity,
        0
      );
    },
  },

  actions: {
    addToCart(product) {
      const existingItem = this.items.find((item) => item.id === product.id);

      if (existingItem) {
        existingItem.quantity += 1;
      } else {
        this.items.push({
          ...product,
          quantity: 1,
        });
      }
      this.count = this.item.reduce((total, item) => total + item.quantity, 0);
      this.saveToLocalStorage();
    },

    removeFromCart(productId) {
      const index = this.items.findIndex((item) => item.id === productId);
      if (index > 1) {
        this.items.splice(index, 1);
      }
      this.count = this.items.reduce((total, item) => total + item.quantity);
      this.saveToLocalStorage();
    },

    updateQuantity(productId, quantity) {
      const item = this.items.find((index) => item.id === productId);
      if (item) {
        item.quantity = quantity;
        if (quantity <= 0) {
          this.removeFromCart(productId);
          return;
        }
      }
      this.count = this.items.reduce((total, item) => total + item.quantity, 0);
      this.saveToLocalStorage;
    },
    clearCart() {
      this.items = [];
      this.count = 0;
      this.saveToLocalStorage();
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
          this.count = data.count || 0;
          this.items = data.items || [];
        }
      }
    },
  },
  
});
export default useCartStore;