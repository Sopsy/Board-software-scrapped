let path = require('path');

module.exports = {
    entry: './src/Index.js',
    output: {
        path: path.resolve(__dirname, '../../static/js'),
        filename: 'yboard.js'
    },
    module: {
        loaders: [
            {
                test: /\.js$/,
                loader: 'babel-loader',
                query: {
                    presets: ['es2015-loose']
                }
            }
        ]
    },
    cache: true
    //devtool: 'source-map'
};
