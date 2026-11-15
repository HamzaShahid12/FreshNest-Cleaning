/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                brand: {
                    DEFAULT: '#2F855A',
                    dark: '#276749',
                    soft: '#E7F5EC',
                    light: '#F7FAF8',
                },
                ink: '#1F2937',
                body: '#374151',
            },
            fontFamily: {
                sans: ['Inter', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif'],
                display: ['Fraunces', 'Georgia', 'ui-serif', 'serif'],
            },
            boxShadow: {
                card: '0 1px 2px rgba(31,41,55,.04), 0 8px 24px -8px rgba(31,41,55,.10)',
            },
        },
    },
    plugins: [],
};
