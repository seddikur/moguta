const gulp = require('gulp'),
    sass = require('gulp-sass'),
    autoprefixer = require('gulp-autoprefixer'),
    babel = require('gulp-babel'),
    uglify = require('gulp-uglify'),
    cleanCSS = require('gulp-clean-css'),
    sourcemaps = require('gulp-sourcemaps'),
    del = require('del'),
    concat = require('gulp-concat');
    plumber = require('gulp-plumber');

function scss() {
    return gulp.src(
        [
            'src/*.scss',
            'node_modules/swiper/dist/css/swiper.min.css'
        ])
        .pipe(sourcemaps.init())
        .pipe(plumber())
        .pipe(sass())
        .pipe(autoprefixer())
        .pipe(cleanCSS({
            level: {
                1: {
                    specialComments: 0
                }
            }
        }))
        .pipe(concat("bundle.css"))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('css'));
}
function adminScss() {
    return gulp.src(
        [
            'src/admin/admin.scss',
        ])
        .pipe(sourcemaps.init())
        .pipe(plumber())
        .pipe(sass())
        .pipe(autoprefixer())
        .pipe(cleanCSS({
            level: {
                1: {
                    specialComments: 0
                }
            }
        }))
        .pipe(concat("admin.css"))
        .pipe(sourcemaps.write())
        .pipe(gulp.dest('css'));
}

function js() {
    return gulp.src(
        [
        'node_modules/swiper/dist/js/swiper.min.js',
        'node_modules/@cycjimmy/swiper-animation/dist/swiper-animation.umd.js',
        ])
        .pipe(plumber())
        .pipe(babel())
        .pipe(uglify())
        .pipe(concat("bundle.js"))
        .pipe(gulp.dest("js"));
}

function clean() {
    return del(['css/bundle.css', 'js/bundle.js'])
}

gulp.task('scss', scss);
gulp.task('js', js);

function watch() {
    gulp.watch('src/style.scss', scss);
}

function admin() {
    gulp.watch('src/admin/admin.scss', adminScss);
}

gulp.task('watch', watch);

gulp.task('admin', admin);

gulp.task('build', gulp.series(
    clean,
    gulp.parallel(scss, js)
));

gulp.task('default', gulp.series('build', 'watch'));

