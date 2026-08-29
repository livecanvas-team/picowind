import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { wordpress, wordpressExternals } from '@nabasa/vp-wp';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite-plus';
import { viteStaticCopy } from 'vite-plugin-static-copy';

const rootDirectory = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig({
    build: {
        target: 'esnext',
        sourcemap: false,
        cssMinify: 'lightningcss',
        minify: true,
    },
    // WordPress exposes the production JSX runtime without jsxDEV.
    oxc: {
        jsx: {
            development: false,
        },
    },
    plugins: [
        tailwindcss(),
        ...wordpress({
            entry: {
                admin: 'resources/admin/main.tsx',
            },
            outDir: 'public/build',
            sourcemap: false,
        }),
        react({
            jsxRuntime: 'automatic',
        }),
        // Base UI still ships a few CommonJS interop paths that call
        // `require('react')`. Bundling React prevents those calls from leaking
        // into the browser while WordPress packages remain external.
        ...(await wordpressExternals({ preset: 'wordpress' })),
        viteStaticCopy({
            targets: [
                {
                    src: 'resources/wp-i18n.js',
                    dest: './',
                },
            ],
        }),
    ],
    worker: {
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].min.js',
                chunkFileNames: 'assets/[name]-[hash].min.js',
            },
        },
    },
    css: {
        transformer: 'lightningcss',
    },
    publicDir: false,
    resolve: {
        alias: [
            { find: '~', replacement: rootDirectory },
            { find: '@/admin', replacement: path.resolve(rootDirectory, 'resources/admin') },
            { find: '@/components', replacement: path.resolve(rootDirectory, 'resources/components') },
            { find: '@', replacement: path.resolve(rootDirectory, 'resources') },
        ],
    },
    server: {
        cors: true,
        allowedHosts: true,
        origin: 'http://localhost:3000',
        port: 3000,
    },
});
