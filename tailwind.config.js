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
                sans: ['"Open Sans"', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#fde8e9',
                    100: '#fbd1d3',
                    200: '#f5a3a7',
                    300: '#ed757b',
                    400: '#e74a52',
                    500: '#9E1D22',
                    600: '#88181c',
                    700: '#701217',
                    800: '#5a0d12',
                    900: '#44080d',
                }
            },
        },
    },
    darkMode: false,
    daisyui: {
        themes: ["light", "dark"],
        darkTheme: "dark",            // Uses 'dark' theme in dark mode detection
    },
    plugins: [forms, require("daisyui")],
};
