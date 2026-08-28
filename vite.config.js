import { fileURLToPath, URL } from 'node:url';

import { defineConfig } from 'vite-plus';
import vue from '@vitejs/plugin-vue';
import { wordpress, wordpressExternals } from '@nabasa/vp-wp';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import tailwindcss from '@tailwindcss/vite';
import Icons from 'unplugin-icons/vite';
import IconsResolver from 'unplugin-icons/resolver';
import Components from 'unplugin-vue-components/vite';

// import { nodePolyfills } from 'vite-plugin-node-polyfills';
// import wasm from 'vite-plugin-wasm';
// import topLevelAwait from 'vite-plugin-top-level-await';
// import svgr from 'vite-plugin-svgr';
// import httpsImports from 'vite-plugin-https-imports';

export default defineConfig({
    // define: {
    //     __dirname: JSON.stringify('/'),
    // },
    plugins: [
        tailwindcss(),
        // wasm(),
        // topLevelAwait(),
        // nodePolyfills({
        //     // Override the default polyfills for specific modules.
        //     overrides: {
        //         fs: 'memfs', // Since `fs` is not supported in browsers, we can use the `memfs` package to polyfill it.
        //     },
        // }),
        ...wordpress({
            entry: {
                admin: 'resources/admin/main.ts',
            },
            outDir: 'public/build',
            sourcemap: false,
        }),
        vue(),
        ...(await wordpressExternals()),
        Icons({
            autoInstall: true,
            scale: 1
        }),
        Components({
            dts: true,
            resolvers: [
                IconsResolver(),
            ],
        }),
        // svgr({
        //     svgrOptions: {
        //         dimensions: false,
        //     }
        // }),
        // httpsImports.default({}, function resolver(matcher) {
        //     return (id, importer) => {
        //         if (matcher(id)) {
        //             return id;
        //         }
        //         else if (matcher(importer) && !id.includes('vite-plugin-node-polyfills')) {
        //             return new URL(id, importer).toString();
        //         }
        //         return undefined;
        //     };
        // }),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/wp-i18n.js',
                    dest: './'
                }
            ]
        })
    ],
    build: {
        sourcemap: false,
        // rollupOptions: {
        //     output: {
        //         chunkFileNames: (chunkInfo) => {
        //             // if the process.env.WP_I18N is available and true, add .min to the vendor module to exclude it from the `wp i18n make-pot` command.
        //             // if (process.env.WP_I18N !== 'true') {
        //             //     return 'chunks/[name]-[hash].min.js';
        //             // }

        //             // add .min to the vendor module to exclude it from the `wp i18n make-pot` command.
        //             // @see https://developer.wordpress.org/cli/commands/i18n/make-pot/

        //             return chunkInfo.name !== 'plugin' && chunkInfo.moduleIds.some(id => id.includes('assets') && !id.includes('node_modules')) ? 'assets/[name]-[hash].js' : 'assets/[name]-[hash].min.js';
        //         },
        //         // entryFileNames: (chunkInfo) => {
        //         //     return process.env.WP_I18N !== 'true' ? "assets/[name]-[hash].min.js" : "assets/[name]-[hash].js";
        //         // },
        //     },
        // },
        // minify: false, // Uncomment this for debugging purposes, otherwise it will minify the code.
        cssMinify: 'lightningcss',
        minify: true
    },
    worker: {
        rollupOptions: {
            output: {
                // add .min to the worker filename to exclude it from the `wp i18n make-pot` command.
                // @see https://developer.wordpress.org/cli/commands/i18n/make-pot/
                entryFileNames: 'assets/[name]-[hash].min.js',
                chunkFileNames: 'assets/[name]-[hash].min.js',
            }
        }
    },
    css: {
        transformer: 'lightningcss',
    },
    publicDir: 'assets/static',
    resolve: {
        alias: {
            '~': fileURLToPath(new URL('.', import.meta.url)), // root directory
            '@': fileURLToPath(new URL('./resources', import.meta.url)),
            // 'source-map-js': 'source-map'
        },
    },
    server: {
        // cors: true,

        // BrowserStackLocal
        allowedHosts: true,
        origin: 'http://localhost:3000',
        port: 3000,
    },
});
