import "./assets/main.css";

import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import { createPinia } from "pinia";
import piniaPluginPersistedstate from "pinia-plugin-persistedstate";
import { setAxiosRouter } from "./lib/axios";

const app = createApp(App);
const pinia = createPinia();

pinia.use(piniaPluginPersistedstate);

setAxiosRouter(router);

app.use(pinia);
app.use(router);

app.mount("#app");
