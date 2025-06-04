import store from "@/store";

// Guard cho user thường
export const authGuard = (to, from, next) => {
  if (store.getters["user/auth/isAuthenticated"]) {
    next();
  } else {
    next("/login");
  }
};

// Guard cho admin
export const adminGuard = (to, from, next) => {
  if (store.getters["admin/adminAuth/isAdminAuthenticated"]) {
    next();
  } else {
    next("/admin/login");
  }
};
