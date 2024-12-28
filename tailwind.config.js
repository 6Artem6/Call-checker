import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    safelist: [
        'w-1/2', 'w-1/4', 'w-3/4', 'w-1/3', 'w-2/3',
        'sm:w-1/2', 'sm:w-1/4', 'sm:w-3/4', 'sm:w-1/3', 'sm:w-2/3',
        'md:w-1/2', 'md:w-1/4', 'md:w-3/4', 'md:w-1/3', 'md:w-2/3',
        'lg:w-1/2', 'lg:w-1/4', 'lg:w-3/4', 'lg:w-1/3', 'lg:w-2/3',
        'xl:w-1/2', 'xl:w-1/4', 'xl:w-3/4', 'xl:w-1/3', 'xl:w-2/3',
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
