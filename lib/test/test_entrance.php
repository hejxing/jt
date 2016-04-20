<?php
/**
 * 项目入口文件
 * 通过服务器的URLRewrite模块将所有的请求转到该文件
 */
$root = realpath(__DIR__ . '/../..');
require $root . '/utils/Debug.php';

\jt\utils\Debug::entrance($root);