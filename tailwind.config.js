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
                },
            },
        },
    },

    plugins: [forms],
};
