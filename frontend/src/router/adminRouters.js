import AdminLayout from "../layout/AdminLayout.vue";
import Dashboard from "../pages/admin/Dashboard.vue";
import LoginAdmin from "../pages/admin/Login.vue";
import Products  from "../pages/admin/components/Product.vue"
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
        name: "Products",
        component: Products,
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
