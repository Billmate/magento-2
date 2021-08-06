'use strict';

var gulp = require('gulp'),
    sass = require('gulp-sass'),
    autoPrefixer = require('gulp-autoprefixer'),
    cleanCss = require('gulp-clean-css'),
    watch = require('gulp-watch'),
    plumber = require('gulp-plumber'),
    cmq = require('gulp-group-css-media-queries');

var config = {
    sass: {
        source: './view/frontend/web/scss/billmate-checkout.scss',
        dist: './view/frontend/web/css'
    }
};

/**
 * Scss task
 */
 gulp.task('scss', function () {
    return gulp.src(config.sass.source)
        .pipe(sass().on('error', sass.logError))
        .pipe(cmq())
        .pipe(autoPrefixer('last 2 versions'))
        .pipe(plumber())
        .pipe(cleanCss())
        .pipe(gulp.dest(config.sass.dist));
});


/**
 * Default task
 */
 gulp.task('watch', function () {
    return gulp.watch([
        './view/frontend/web/scss/**/*.scss'
    ], gulp.series([
        'scss'
    ]));
});