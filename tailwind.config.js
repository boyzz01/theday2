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
            colors: {
                brand: {
                    primary: '#92A89C',
                    'primary-hover': '#73877C',
                    'primary-soft': '#B8C7BF',
                    premium: '#C8A26B',
                    'premium-hover': '#B8905A',
                    text: '#2C2417',
                    bg: '#FFFCF7',
                },
            },
        },
    },

    plugins: [forms],
};
