'use strict';

const path = require('path'),
    __basename = path.dirname(__dirname);

module.exports = {
    path: {
        src: path.resolve(__basename, "src"),
        dist: path.resolve(__basename, "build/menu"),
        pub: path.resolve(__basename, "build/pub")
    },
    htmlPath: '',
    publicPath: '//127.0.0.1:8088/build/',
    chunkhash: "",
    hash: "",
    contenthash: ""
};
