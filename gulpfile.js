//Install dependencies: npm install gulp gulp-watch gulp-sass gulp-autoprefixer del

var gulp = require('gulp');
var watch = require('gulp-watch');
var sass = require('gulp-sass');
var autoprefixer = require('gulp-autoprefixer');

gulp.task('sass', function () {
    gulp.src('./scss/*.scss')
        .pipe(sass())
        .pipe(autoprefixer({
            remove: false,
            browsers: ['last 2 versions', 'ie >= 8']
        }))
        .pipe(gulp.dest('./css'));
});

gulp.task('watch', function() {
    gulp.watch('./scss/*.scss', ['sass']);
});
