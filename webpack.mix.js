const mix = require('laravel-mix')
const config = require('./webpack.config.js')

mix.setPublicPath('public/dist')

mix.webpackConfig({ devtool: 'source-map' })

mix.browserSync({
    proxy: config.projectURL,
    files: [
        './resources/views/**/*.php',
        './resources/css/**/*.css',
        './resources/js/**/*.js',
        './tailwind.config.js',
    ],
})

mix.override((config) => {
    config.watchOptions = {
        ignored: /node_modules/,
    }
})

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
mix.postCss('resources/css/images.css', '', [
    require('postcss-import'),
    require('autoprefixer'),
])

mix.js('resources/js/editor.js', '')
mix.postCss('resources/css/editor.css', '', [
    require('postcss-import'),
    require('tailwindcss'),
    require('autoprefixer'),
])

if (mix.inProduction()) {
    mix.version()
}
