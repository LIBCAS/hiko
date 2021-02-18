const config = require('./wpgulp.config.js')

const BROWSERS_LIST = ['last 2 version']

const paths = {
    styleSRC: './assets/css/main.scss',
    styleDestination: './assets/dist',

    jsCustomSRC: './assets/js/custom/',
    jsCustomDestination: './assets/dist/',
    jsCustomFile: 'custom',

    imgSRC: './assets/img/raw/*',
    imgDST: './assets/img/',

    watchStyles: './assets/css/**/*.scss',
    watchJsVendor: './assets/js/vendor/*.js',
    watchJsCustom: './assets/js/custom/*.js',
    watchPhp: './**/*.php',
}

const gulp = require('gulp')
const sass = require('gulp-sass')
const autoprefixer = require('gulp-autoprefixer')
const concat = require('gulp-concat')
const uglify = require('gulp-uglify')
const babel = require('gulp-babel')
const imagemin = require('gulp-imagemin')
const rename = require('gulp-rename')
const lineec = require('gulp-line-ending-corrector')
const sourcemaps = require('gulp-sourcemaps')
const notify = require('gulp-notify')
const browserSync = require('browser-sync').create()
const wpPot = require('gulp-wp-pot')
const sort = require('gulp-sort')
const cache = require('gulp-cache')
const remember = require('gulp-remember')
const plumber = require('gulp-plumber')
const beep = require('beepbeep')
const del = require('del')

const errorHandler = (r) => {
    notify.onError('❌  ===> ERROR: <%= error.message %>')(r)
    beep()
}

const browsersync = (done) => {
    browserSync.init({
        proxy: config.projectURL,
        open: false,
        injectChanges: true,
        watchEvents: ['change', 'add', 'unlink', 'addDir', 'unlinkDir'],
    })
    done()
}

const reload = (done) => {
    browserSync.reload()
    done()
}

gulp.task('styles', () => {
    return gulp
        .src(paths.styleSRC, { allowEmpty: true })
        .pipe(plumber(errorHandler))
        .pipe(sourcemaps.init())
        .pipe(
            sass({
                errLogToConsole: true,
                outputStyle: 'compressed',
                precision: 10,
            })
        )
        .on('error', sass.logError)
        .pipe(autoprefixer(BROWSERS_LIST))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(paths.styleDestination))

        .pipe(browserSync.stream())
        .pipe(
            notify({
                message: '✅  ===> STYLES — completed!',
                onLast: true,
            })
        )
})

gulp.task('customJS', () => {
    return gulp
        .src(
            [
                paths.jsCustomSRC + 'global.js',
                paths.jsCustomSRC + 'utils.js',
                paths.jsCustomSRC + '!(global|utils)*.js',
            ],
            { since: gulp.lastRun('customJS') }
        )
        .pipe(plumber(errorHandler))
        .pipe(sourcemaps.init())
        .pipe(
            babel({
                presets: [
                    [
                        '@babel/preset-env',
                        {
                            targets: { browsers: BROWSERS_LIST },
                        },
                    ],
                ],
            })
        )
        .pipe(remember(paths.jsCustomSRC + '*.js'))
        .pipe(concat(paths.jsCustomFile + '.js'))
        .pipe(lineec())
        .pipe(gulp.dest(paths.jsCustomDestination))
        .pipe(
            rename({
                basename: paths.jsCustomFile,
                suffix: '.min',
            })
        )
        .pipe(uglify())
        .pipe(lineec())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest(paths.jsCustomDestination))
        .pipe(
            notify({
                message: '✅  ===> CUSTOM JS — completed!',
                onLast: true,
            })
        )
})

gulp.task('images', () => {
    return gulp
        .src(paths.imgSRC)
        .pipe(
            cache(
                imagemin([
                    imagemin.gifsicle({ interlaced: true }),
                    imagemin.jpegtran({ progressive: true }),
                    imagemin.optipng({ optimizationLevel: 3 }), // 0-7 low-high.
                    imagemin.svgo({
                        plugins: [
                            { removeViewBox: true },
                            { cleanupIDs: false },
                        ],
                    }),
                ])
            )
        )
        .pipe(gulp.dest(paths.imgDST))
        .pipe(
            notify({
                message: '✅  ===> IMAGES — completed!',
                onLast: true,
            })
        )
})

gulp.task('cleanBuild', function () {
    del(['build/**/*'])
    return gulp
        .src([
            './**/*',
            '!node_modules',
            '!node_modules/**',
            '!build',
            '!build/**',
            '!assets/css',
            '!assets/css/**',
            '!assets/js/custom/**',
            'assets/js/vendor/**',
            '!assets/img/raw',
            '!assets/img/raw/**',
            '!package-lock.json',
            '!package.json',
            '!composer.lock',
            '!composer.json',
            '!prettier.config.js',
            '!wpgulp.config.js',
            '!wpgulp.config.sample.js',
            '!gulpfile.babel.js',
        ])
        .pipe(gulp.dest('build'))
        .pipe(
            notify({
                message: 'Clean Build — completed!',
                onLast: true,
            })
        )
})

gulp.task('clearCache', function (done) {
    return cache.clearAll(done)
})

gulp.task('translate', () => {
    return gulp
        .src(paths.watchPhp)
        .pipe(sort())
        .pipe(
            wpPot({
                domain: 'hiko',
                package: 'hiko',
                bugReport: 'pachlova@lib.cas.cz',
                lastTranslator: 'Jarka P <pachlova@lib.cas.cz>',
                team: 'Jarka P <pachlova@lib.cas.cz>',
            })
        )
        .pipe(gulp.dest('./languages/hiko.pot'))
        .pipe(
            notify({
                message: '✅  ===> TRANSLATE — completed!',
                onLast: true,
            })
        )
})

gulp.task(
    'default',
    gulp.parallel('styles', 'customJS', browsersync, () => {
        gulp.watch(paths.watchPhp, reload) // Reload on PHP file changes.
        gulp.watch(paths.watchStyles, gulp.parallel('styles')) // Reload on SCSS file changes.
        gulp.watch(paths.watchJsCustom, gulp.series('customJS', reload)) // Reload on customJS file changes.
    })
)
