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
                sans: [
                    '-apple-system',
                    'BlinkMacSystemFont',
                    '"Segoe UI"',
                    'Roboto',
                    '"Helvetica Neue"',
                    'Arial',
                    'sans-serif',
                ],
            },
            colors: {
                sidebar: {
                    DEFAULT: '#1a2332',
                    hover:   '#243042',
                    border:  'rgba(255,255,255,0.10)',
                },
                sensitive: {
                    DEFAULT: '#f97316',  // orange-500
                    hover:   '#ea580c',  // orange-600
                    soft:    '#fff7ed',  // orange-50
                    text:    '#c2410c',  // orange-700
                    border:  '#fdba74',  // orange-300
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgba(15,23,42,0.04)',
                cart: '-4px 0 24px -4px rgba(15,23,42,0.10)',
            },
            transitionTimingFunction: {
                'cart-slide': 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
    },

    plugins: [forms],
};
