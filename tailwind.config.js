import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',

        /*
         * Las paletas de la Super Edicion viven en clases PHP: un grupo o
         * una puerta llevan sus clases de color como datos, porque el color
         * tiene que ser el mismo en el panel, en la tabla, en la jornada y
         * en la salida.
         *
         * Sin esta linea Tailwind no las veia y no generaba el CSS: los
         * anillos, los bordes y los fondos suaves existian en el HTML pero
         * no pintaban nada.
         */
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
