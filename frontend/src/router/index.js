import {createRouter, createWebHistory} from "vue-router";
import { userRouters } from "./userRoutes.js";
import {adminRouters} from "./adminRouters.js";
const routes = [
  // User routes
  ...userRouters,
  ...adminRouters,
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
