import {createRouter, createWebHistory} from "vue-router";
import { userRouters } from "./userRoutes.js";

const routes = [
  // User routes
  ...userRouters,
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
