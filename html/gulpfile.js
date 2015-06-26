var gulp = require('gulp');
var inject = require('gulp-inject');
var mainBowerFiles = require('main-bower-files');

gulp.task('index', function() {

    var target = gulp.src('./app/index.html');

    var sources = gulp.src(mainBowerFiles({
        debugging:true,
        includeSelf:true
    }));

    return target.pipe(inject(sources,{addRootSlash:false})).pipe(gulp.dest('./'));

});
