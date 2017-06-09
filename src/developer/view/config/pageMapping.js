//配置访问入口
module.exports = [
    {name: '../error/404', entry: {name: 'e_404', js: []}, tpl: 'error_tpl/404.html'},
    {name: '../error/error', entry: {name: 'e_err', js: []}, tpl: 'error_tpl/error.html'},
    {name: '../error/layout', entry: {name: 'e_layout', js: []}, tpl: 'error_tpl/layout.html'},
    {name: 'index', entry: {name: 'menu', js: ['menu/route_rule.js']}},
];