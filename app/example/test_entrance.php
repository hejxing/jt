<?php
/**
 * 项目入口文件
 * 通过服务器的URLRewrite模块将所有的请求转到该文件
 */

require __DIR__ . '/../../jt/Bootstrap.php';

\jt\Bootstrap::test(__DIR__);