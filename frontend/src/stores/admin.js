import { defineStore } from "pinia";
import axiosAdmin from "../axiosAdmin.js";

export const useAdminStore = defineStore("admin", {
  state: () => ({
    user: null,
  }),
  
  getters: {
    isAdmin: (state) => {
      return state.user && state.user.isAdmin === true;
    }
  },
  
  actions: {
    async fetchUserAdmin() {
      try {
        const response = await axiosAdmin.get("/api/user");
        console.log("User data fetched:", response.data);
        this.user = response.data;
      } catch (error) {
        console.error("Fetch user error:", error);
      }
    },
  }
});

export default useAdminStore;