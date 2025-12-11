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

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],


    safelist: [
        // Green background (active + hover)
        'bg-green-500',
        'bg-green-600',
        'hover:bg-green-500',
        'hover:bg-green-600',

        // Text colors
        'text-white',
        'hover:text-white',
        'text-gray-700',
        'text-gray-800',
        'text-black',

        // Shadows
        'shadow-md',
        'shadow-lg',
        'hover:shadow-md',
        'hover:shadow-lg',

        // Optional: If you use other green shades
        'bg-emerald-500',
        'bg-emerald-600',
        'hover:bg-emerald-500',
        'hover:bg-emerald-600',
    ],
};