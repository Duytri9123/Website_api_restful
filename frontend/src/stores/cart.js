import { defineStore } from "pinia";
import axiosClient from "../axiosClient";

export const useCartStore = defineStore("cart", {
  state: () => ({
    items: [],
    count: 0,
    isAuthenticated: false, 
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
    async addToCart(product, variant, quantity = 1) {
      if (this.isAuthenticated) {
        // Gọi API khi user đã login
        const res = await axiosClient.post("/cart", {
          product_id: product.id,
          variant_id: variant.id,
          quantity,
        });
        this.items = res.data.items; // server trả về danh sách giỏ hàng
        this.updateCount();
      } else {
        // Guest cart -> localStorage
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
      }
    },

    async removeFromCart(variantId) {
      if (this.isAuthenticated) {
        await axiosClient.delete(`/cart/${variantId}`);
        this.items = this.items.filter((item) => item.variant.id !== variantId);
        this.updateCount();
      } else {
        this.items = this.items.filter((item) => item.variant.id !== variantId);
        this.updateCount();
        this.saveToLocalStorage();
      }
    },

    async updateQuantity(variantId, quantity) {
      if (this.isAuthenticated) {
        await axiosClient.put(`/cart/${variantId}`, { quantity });
        const item = this.items.find((i) => i.variant.id === variantId);
        if (item) {
          item.quantity = quantity > 0 ? quantity : 1;
        }
      } else {
        const item = this.items.find((i) => i.variant.id === variantId);
        if (item) {
          item.quantity = quantity > 0 ? quantity : 1;
        }
        this.saveToLocalStorage();
      }
      this.updateCount();
    },

    async fetchUserCart() {
      if (this.isAuthenticated) {
        const res = await axiosClient.get("/cart");
        this.items = res.data.items;
        this.updateCount();
      }
    },

    async syncCartAfterLogin() {
      if (!this.isAuthenticated) return;

      // Nếu localStorage có giỏ hàng guest thì sync lên server
      const saved = localStorage.getItem("cart");
      if (saved) {
        const data = JSON.parse(saved);
        if (data.items.length > 0) {
          await axiosClient.post("/cart/sync", { items: data.items });
          localStorage.removeItem("cart");
        }
      }
      await this.fetchUserCart();
    },

    clearCart() {
      this.items = [];
      this.count = 0;
      if (!this.isAuthenticated) {
        this.saveToLocalStorage();
      }
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
