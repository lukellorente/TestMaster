import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from '../../Vue/router';
import App from '../../Vue/App.vue';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount('#app');