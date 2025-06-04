import {createRouter, createWebHistory} from "vue-router";
// Import user and admin routes
import { userRouters } from "./userRoutes.js";
// import { adminRouters } from "./adminRoutes.js";
// import { authGuard, adminGuard } from "./guards.js";

const routes = [
  // User routes
  ...userRouters,

  // Admin routes with guards
  // map duyet qua tung adminRouters va them vao route
  // ...adminRouters.map((route) => ({
  //   // Spread the route properties
  //   ...route,
  //   path: `/admin${route.path}`, // Prefix admin routes with /admin
  //   beforeEnter: adminGuard, // Apply admin guard to all admin routes
  // })),
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
