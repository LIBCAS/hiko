const colors = require('tailwindcss/colors')
const defaultTheme = require('tailwindcss/defaultTheme')

module.exports = {
    important: true,
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                gray: colors.stone,
                primary: colors.violet['700'],
                'primary-light': colors.violet['400'],
                'primary-dark': colors.violet['900'],
            },
            fontFamily: {
                sans: [
                    'Atkinson Hyperlegible',
                    ...defaultTheme.fontFamily.sans,
                ],
            },
            outline: {
                primary: '2px solid ' + colors.violet['700'],
            },
        },
    },
    plugins: [
        require('@tailwindcss/forms'),
        require('@tailwindcss/typography'),
    ],
}
