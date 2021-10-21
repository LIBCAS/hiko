const mix = require('laravel-mix')

mix.setPublicPath('public/dist')

mix.webpackConfig({ devtool: 'source-map' })

mix.options({
    processCssUrls: false,
})

mix.js('resources/js/app.js', '')

mix.postCss('resources/css/app.css', '', [
    require('postcss-import'),
    require('tailwindcss'),
    require('autoprefixer'),
])

mix.js('resources/js/images.js', '')
mix.postCss('resources/css/images.css', '', [require('postcss-import')])

mix.js('resources/js/editor.js', '')
