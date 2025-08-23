import { defineStore } from "pinia";

export const useFavoriteStore = defineStore("favorite", {
  state: () => ({
    favorites: new Set(),
  }),

  getters: {
    favoriteList: (state) => Array.from(state.favorites),
    isFavorite: (state) => (id) => state.favorites.has(id),

    favoriteCount: (state) => state.favorites.size,
  },

  actions: {
    loadFromLocalStorage() {
      try {
        const saved = localStorage.getItem("favorites");
        if (saved) {
          this.favorites = new Set(JSON.parse(saved));
        }
      } catch (err) {
        console.error("Error loading favorites:", err);
      }
    },

    saveToLocalStorage() {
      try {
        localStorage.setItem("favorites", JSON.stringify([...this.favorites]));
      } catch (err) {
        console.error("Error saving favorites:", err);
      }
    },

    toggleFavorite(id) {
      if (this.favorites.has(id)) {
        this.favorites.delete(id);
      } else {
        this.favorites.add(id);
      }
      this.saveToLocalStorage();
    },

    clearFavorites() {
      this.favorites.clear();
      this.saveToLocalStorage();
    },
  },
});
