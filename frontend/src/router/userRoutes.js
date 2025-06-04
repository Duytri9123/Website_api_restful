import Home from "../pages/user/Home.vue";
import Contact from "../pages/user/Contact.vue";
import Login from "../pages/user/auth/Login.vue";
import Signup from "../pages/user/auth/Signup.vue";
import NotFound from "../pages/user/NotFound.vue";
// import Dashboard from "../pages/user/dashboard/Dashboard.vue";
import DefaultLayout from "../layouts/user/DefaultLayout.vue";

export const userRouters = [
  {
    path: "/",
    component: DefaultLayout,
    children: [
      {
        path: "/",
        name: "Home",
        component: Home,
      },
      {
        path: "/contact",
        name: "Contact",
        component: Contact,
      },
    ],
  },
  // Các route khác
  {
    path: "/login",
    name: "Login",
    component: Login,
  },
  {
    path: "/signup",
    name: "Signup",
    component: Signup,
  },
  {
    path:'/:pathMatch(.*)*',
    name: "NotFound",
    component: NotFound,
  }
  // {
  //   path: "/dashboard",
  //   name: "Dashboard",
  //   component: Dashboard,
  //   beforeEnter: authGuard, // Yêu cầu đăng nhập
  // },
];
