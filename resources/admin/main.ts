console.log('Admin main.ts loaded');

import './styles/main.css'

import './wp-admin';

import { createApp } from 'vue';
import { createPinia, PiniaPluginContext } from 'pinia'
// import ui from '@nuxt/ui/vue-plugin'
// import { install as VueMonacoEditorPlugin } from '@guolao/vue-monaco-editor';

import App from './App.vue';
import router from './router';

// import i18nPlugin from './plugins/i18n';

const app = createApp(App)
const pinia = createPinia()

app.config.globalProperties.window = window;

// // Pinia plugin: Initialize the store with the data from the server.
// pinia.use(({ store }: PiniaPluginContext) => {
//     if (['volume', 'settings'].includes(store.$id)) {
//         store.initPull();
//     }
// })

app
    .use(router)
    .use(pinia)
//     .use(ui)
//     .use(VueMonacoEditorPlugin)
//     .use(i18nPlugin)

app.mount('#picowind-app')
