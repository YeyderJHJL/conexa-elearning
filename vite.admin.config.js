import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

/*
 * Compilación aislada del tema del panel de Filament.
 *
 * Los temas de Filament necesitan Tailwind 4, pero el sitio del trabajador usa Tailwind 3.4
 * (tailwind.config.js + PostCSS). Para no tocar las dependencias ni el build del sitio, este
 * segundo build usa el Tailwind 4 que ya trae @tailwindcss/vite y deja el resultado fijo en
 * public/build-admin/theme.css, que se registra en AdminPanelProvider con ->theme().
 *
 * Uso: `npm run build:admin` (o `npm run build`, que compila el sitio y el tema).
 */

const raiz = path.dirname(fileURLToPath(import.meta.url));

// Tailwind 4 se instala anidado dentro de los paquetes de @tailwindcss/*.
const tailwind4 = [
    'node_modules/@tailwindcss/vite/node_modules/tailwindcss',
    'node_modules/@tailwindcss/node/node_modules/tailwindcss',
]
    .map((ruta) => path.resolve(raiz, ruta))
    .find((ruta) => fs.existsSync(path.join(ruta, 'index.css')));

if (!tailwind4) {
    throw new Error('No se encontró Tailwind 4 (tailwindcss/index.css) dentro de @tailwindcss/vite. Ejecuta `npm install`.');
}

export default defineConfig({
    plugins: [tailwindcss()],

    // No copiar public/ dentro del resultado: outDir está dentro de public/.
    publicDir: false,

    // El postcss.config.js del sitio usa Tailwind 3; este build no debe cargarlo.
    css: { postcss: {} },

    resolve: {
        alias: {
            // El `@import 'tailwindcss'` del tema de Filament debe resolverse a Tailwind 4, no al 3.4 de la raíz.
            tailwindcss: tailwind4,
        },
    },

    build: {
        outDir: 'public/build-admin',
        emptyOutDir: true,
        rollupOptions: {
            input: 'resources/css/filament/admin/theme.css',
            output: {
                assetFileNames: 'theme.css',
            },
        },
    },
});
