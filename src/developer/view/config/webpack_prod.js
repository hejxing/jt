'use strict';

const path = require('path'),
    __basename = path.dirname(__dirname);

module.exports = {
    path: {
        src: path.resolve(__basename, "src"),
        dist: path.resolve(__basename, "static/menu"),
        pub: path.resolve(__basename, "pub")
    },
    htmlPath: '',
    publicPath: '//docs-resource.csmall.com/menu/',
    chunkhash: "-[chunkhash:6]",
    hash: "-[hash:6]",
    contenthash: "-[contenthash:6]"
};
