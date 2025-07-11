import { createApp } from "vue";
import "./style.css";
import App from "./App.vue";
import router from "./router/index.js";
import { createPinia } from "pinia";
import { initFlowbite } from "flowbite";
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'

const pinia = createPinia();

const app = createApp(App);
app.use(router);
app.use(pinia);
app.use(ElementPlus)
for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
  app.component(key, component)
};
app.mount("#app");

initFlowbite();
