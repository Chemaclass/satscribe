// tailwind.config.js
export default {
    darkMode: 'class', // or 'media' if you want system preference
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {},
    },
    plugins: [],
    safelist: [
        'description-item',
        'description-question',
    ],
}
