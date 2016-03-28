<?php
/**
 * 项目入口文件
 * 通过服务器的URLRewrite模块将所有的请求转到该文件
 */
$root = realpath(__DIR__ . '/../..');
require $root . '/Bootstrap.php';

$pwd = $_SERVER['PWD'];

$pos = strpos($pwd, '/app/');
if ($pos) {
    $root = implode('/', array_slice(explode('/', $pwd, 6), 0, 5));
}

\jt\Bootstrap::test($root);
if (file_exists($root . '/test_entrance.php')) {
    require $root . '/test_entrance.php';
}