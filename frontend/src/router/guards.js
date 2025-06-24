import { useUserStore } from "../stores/user.js";
import { useAdminStore } from "../stores/admin.js";

// Guard cho user thường
export const userGuard = async (to, from, next) => {
  try {
    const userStore = useUserStore();
    await userStore.fetchUser();
    next();
  } catch (error) {
    console.error("Error fetching user:", error);
    next(false);
  }
};

// Guard cho admin
export const adminGuard = async (to, from, next) => {
  try {
    const adminStore = useAdminStore();
    await adminStore.fetchUserAdmin();

    // Kiểm tra xem có lấy được thông tin user và có phải admin không
    if (adminStore.user && adminStore.isAdmin) {
      next();
    } else {
      next("/admin/login");
    }
  } catch (error) {
    console.error("Error fetching admin user:", error);
    next("/admin/login");
  }
};
