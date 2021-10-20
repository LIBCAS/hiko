const colors = require('tailwindcss/colors')

module.exports = {
    important: true,
    purge: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                gray: colors.warmGray,
                primary: colors.violet['700'],
                'primary-light': colors.violet['400'],
                'primary-dark': colors.violet['900'],
            },
            outline: {
                primary: '2px solid ' + colors.violet['700'],
            },
        },
    },
    variants: {
        animation: ['responsive', 'motion-safe', 'motion-reduce'],
        extend: {
            opacity: ['disabled'],
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
