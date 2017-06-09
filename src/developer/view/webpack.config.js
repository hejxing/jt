const path = require('path');
const webpack = require('webpack');
const CommonsChunkPlugin = require("webpack/lib/optimize/CommonsChunkPlugin");
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const HtmlResWebpackPlugin = require('html-res-webpack-plugin');
const CleanPlugin = require('clean-webpack-plugin');
const autoPreFixer = require('autoprefixer');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const config = require('./config/webpack_' + (process.env.NODE_ENV === 'production'? 'prod': 'dev') + '.js');
const pageMapping = require('./parsePageMapping')(config);

const webpackConfig = {
    entry: pageMapping.entry,
    output: {
        path: config.path.dist,
        publicPath: config.publicPath,
        filename: "js/[name]" + config.chunkhash + ".js",
        chunkFilename: "js/[chunkhash:8]_[id].js"
    },
    addPlugins: function(plugin, opt = false){
        this.plugins.push(new plugin(opt));
    },
    plugins: [
        new CommonsChunkPlugin({
            filename: "js/comm" + config.chunkhash + ".js",
            //children:  true,
            name: "comm",
            minChunks: 2
        }),
        new ExtractTextPlugin({
            "filename": "css/[name]" + config.contenthash + ".css"
        })
    ],
    resolve: {
        extensions: ['', '.js', '.jsx'],
        //root: './src',
        alias: {
            //'ajax': './src/ajax'
        }
    },
    resolveLoader: {
        root: path.join(__dirname, 'node_modules'),
    },
    module: {
        loaders: [
            {
                test: /\.js|\.jsx$/,
                exclude: /node_modules/,
                loader: "react-hot-loader!babel-loader"
            },
            {
                test: /\.css$/,
                loader: ExtractTextPlugin.extract({fallback: 'style-loader', use: 'css-loader!postcss-loader'})
            },
            {
                test: /\.sass/,
                loader: ExtractTextPlugin.extract({fallback: 'style-loader', use: 'css-loader!postcss-loader!sass-loader'})
            },
            {
                test: /\.less$/,
                loader: 'style-loader!css-loader!postcss-loader!less-loader'
            },
            {
                test: /\.(html|tpl)$/,
                loader: 'html-loader'
            },
            {
                test: /\.(eot|svg|ttf|woff|woff2)$/,
                loader: 'file-loader'
            },
            {
                test: /\.(png|jpg|gif|svg)$/,
                loader: 'file-loader',
                query: {
                    name: '[name].[ext]'
                }
            }
        ]
    },
    options: {
        postcss: function(){
            return [autoPreFixer({browsers: ['last 2 versions']})];
        },
    },
    devtool: '#eval-source-map'
};

let htmlMinify = {
    removeComments: process.env.NODE_ENV === 'production',
    collapseWhitespace: process.env.NODE_ENV === 'production'
};

pageMapping.page.forEach(function(page){
    webpackConfig.addPlugins(HtmlResWebpackPlugin, {
        filename: config.htmlPath + page.name + ".html",
        template: page.tpl,
        chunks: page.chunks,
        htmlMinify: htmlMinify
    });
});

if(process.env.NODE_ENV === 'production'){
    webpackConfig.devtool = '#source-map';
// http://vue-loader.vuejs.org/en/workflow/production.html
    webpackConfig.addPlugins(webpack.DefinePlugin, {
        'process.env': {
            NODE_ENV: '"production"'
        }
    });
    webpackConfig.addPlugins(webpack.optimize.UglifyJsPlugin, {
        compress: {
            warnings: false
        }
    });
    webpackConfig.addPlugins(webpack.optimize.OccurrenceOrderPlugin);
    webpackConfig.addPlugins(CleanPlugin, config.path.dist);
}else if(process.env.NODE_ENV === 'debugging'){
    webpackConfig.addPlugins(webpack.optimize.OccurrenceOrderPlugin);
    webpackConfig.addPlugins(CleanPlugin, config.path.dist);
}

// webpackConfig.plugins.push(new CopyWebpackPlugin([
//     {from: 'from/file.txt', to: 'to/file.txt'}
// ], {
//     ignore: [],
//     copyUnmodified: true
// }));

const matched = config.publicPath.match(/\/\/([^:\/]*):?([^\/]*)/) || [];

webpackConfig.devServer = {
    historyApiFallback: {
        rewrites: [{
            from: /(\/*?\/)/,
            to: function(option){
                return option.match[1];
            }
        }],
        index: '/index.tpl'
    },
    noInfo: true,
    host:  matched[1] || '127.0.0.1',
    port: matched[2] || '8088',
    contentBase: "build/",
    headers: {"Access-Control-Allow-Origin": "*"}
};

module.exports = webpackConfig;