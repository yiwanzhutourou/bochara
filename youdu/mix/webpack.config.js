module.exports = (mix, program) => {

    mix.options({
        imgLoaderOptions: {
            enabled: false,
        },
        postCss: [
            require('autoprefixer')({browsers: [
                'Android >= 4',
                'Chrome >= 20',
                'Firefox >= 24',
                'Explorer >= 8',
                'iOS >= 6',
                'Opera >= 12',
                'Safari >= 6',
            ]}),
        ],
    });

    if (program.MIX_PROXY) {
        mix.browserSync({
            notify: false,
            proxy: program.MIX_PROXY,
            files: [
                'resources/views/**/*.php',
                'resources/assets/**/*.*'
            ]
        });
    }
    mix.webpackConfig({
        module: {
            rules: [{
                test: /\.styl$/,
                loader: 'stylus-loader',
            }, {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        plugins:[["component",{
                            "libraryName": "element-ui",
                            "styleLibraryName": "theme-chalk",
                            "style": true 
                        }]]
                    }
                }
            }, {
                test: /\.(png|jpe?g|gif)$/,
                loader: 'url-loader',
                options: {
                    limit: 8000,
                    name: path => {
                        if (!/node_modules|bower_components/.test(path)) {
                            return 'images/[name].[ext]?[hash]';
                        }

                        return 'images/vendor/' + path
                                .replace(/\\/g, '/')
                                .replace(
                                    /((.*(node_modules|bower_components))|images|image|img|assets)\//g, ''
                                ) + '?[hash]';
                    },
                    publicPath: '/',  //todo 待调整
                }
            }]
        }
    })

    if (program.production) {
        mix.version()
    } else {
        mix.sourceMaps()
    }

}
