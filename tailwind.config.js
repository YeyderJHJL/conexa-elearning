import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },

            boxShadow: {
                tarjeta: '0 1px 2px rgb(14 26 52 / 0.05), 0 10px 28px -12px rgb(14 26 52 / 0.18)',
                'tarjeta-hover': '0 2px 4px rgb(14 26 52 / 0.06), 0 22px 44px -14px rgb(14 26 52 / 0.28)',
                nodo: '0 8px 16px -6px rgb(14 26 52 / 0.38)',
            },

            keyframes: {
                // Halo dorado que respira alrededor del nodo actual de la ruta.
                pulso: {
                    '0%, 100%': { boxShadow: '0 0 0 0 rgb(215 167 67 / 0.55)' },
                    '50%': { boxShadow: '0 0 0 16px rgb(215 167 67 / 0)' },
                },
                aparecer: {
                    from: { opacity: '0', transform: 'translateY(10px)' },
                    to: { opacity: '1', transform: 'none' },
                },
            },

            animation: {
                pulso: 'pulso 2.6s ease-in-out infinite',
                aparecer: 'aparecer 0.45s ease-out both',
            },

            // Paleta oficial de Conexa Capital Central (clases `bg-marca-azul`, `text-marca-dorado`, etc.).
            // Debe coincidir con config/marca.php y con las variables CSS de resources/css/app.css.
            colors: {
                marca: {
                    azul: '#0E1A34',
                    dorado: '#D7A743',
                    gris: '#8C8C8E',
                    complementario: '#4A6FA5',
                    profundo: '#1F2F4A',
                    'gris-claro': '#B3B3B3',
                    // Derivado accesible del gris para texto pequeño (ver config/marca.php).
                    'gris-texto': '#6E6E70',
                    // Fondo suave de las páginas (no es un color de marca).
                    fondo: '#F3F5F9',
                },
            },
        },
    },

    plugins: [forms],
};
