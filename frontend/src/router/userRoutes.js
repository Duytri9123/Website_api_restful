import Home from "../pages/Home.vue";
import Contact from "../pages/Contact.vue";
import About from "../pages/About.vue";
import ProductDetail from "../pages/user/products/ProductDetail.vue";
import Cart from "../pages/user/cart/Cart.vue";
import Login from "../pages/auth/Login.vue";
import Signup from "../pages/auth/Signup.vue";
import NotFound from "../pages/NotFound.vue";
import DefaultLayout from "../layout/DefaultLayout.vue";
import { userGuard } from "../router/guards.js";
export const userRouters = [
  {
    path: "/",
    component: DefaultLayout,
    beforeEnter: userGuard,
    children: [
      {
        path: "",
        name: "Home",
        component: Home,
      },
      {
        path: "contact",
        name: "Contact",
        component: Contact,
      },
      {
        path: "about",
        name: "About",
        component: About,
      },
      {
        path:"products/:id",
        name:"ProductDetail",
        component:ProductDetail,
      },
      {
        path:"cart",
        name:"Cart",
        component:Cart,
      }
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
    path: "/:pathMatch(.*)*",
    name: "NotFound",
    component: NotFound,
  },
];
