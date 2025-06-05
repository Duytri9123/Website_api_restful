import { defineStore } from "pinia";
import axiosClient from "../../../axios";

export const useUserStore = defineStore("user", {
  state: () => ({
    user: null,
  }),
  actions: {
    async fetchUser() {
      const { data } = await axiosClient.get("/api/user");
      console.log(data);
      this.user = data;
    },
  },
});

export default useUserStore;