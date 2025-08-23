import AdminLayout from "../layout/AdminLayout.vue";
import Dashboard from "../pages/admin/Dashboard.vue";
import LoginAdmin from "../pages/admin/Login.vue";

import ProductManager from "../pages/admin/components/ProductManager.vue";
import CategoriesBrands from "../pages/admin/components/CategoryBrandManager.vue";
import Member from "../pages/admin/components/TeamMember.vue";
import { adminGuard } from "../router/guards.js";
export const adminRouters = [
  {
    path: "/admin",
    component: AdminLayout,
    redirect: "/admin/dashboard",
    beforeEnter: adminGuard,
    children: [
      {
        path: "dashboard",
        name: "Dashboard",
        component: Dashboard,
      },
      {
        path: "products",
        name: "ProductManager",
        component: ProductManager,
      },
      {
        path: "categorybrand",
        name: "CategoriesBrands",
        component: CategoriesBrands,
      },
      {
        path: "member",
        name: "member",
        component: Member,
      },
    ],
  },
  {
    path: "/admin/login",
    name: "LoginAdmin",
    component: LoginAdmin,
  },
];
