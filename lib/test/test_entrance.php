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

$testClass = $_SERVER['argv'][4];
$testFile = $_SERVER['argv'][5];

$projectRoot = explode(str_replace('\\',DIRECTORY_SEPARATOR, $testClass), $testFile)[0];
$projectRoot = substr($projectRoot, 0 ,-1);
//\jt\Bootstrap::test($root);
if (file_exists($projectRoot . '/test_entrance.php')) {
    require $projectRoot . '/test_entrance.php';
}else{
    \jt\Bootstrap::test($projectRoot);
}