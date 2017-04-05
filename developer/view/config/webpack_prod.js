'use strict';

const path = require('path'),
    __basename = path.dirname(__dirname);

module.exports = {
    path: {
        src: path.resolve(__basename, "src"),
        dist: path.resolve(__basename, "static/grant"),
        pub: path.resolve(__basename, "pub")
    },
    htmlPath: '',
    publicPath: '//jt-static.test.csmall.com/',
    chunkhash: "-[chunkhash:6]",
    hash: "-[hash:6]",
    contenthash: "-[contenthash:6]"
};
