<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2016/9/2
 * Time: 10:32
 */

namespace jt\utils;

use jt\Bootstrap;

require __DIR__.'/../Bootstrap.php';

class Cli
{
    public static function boot($argv, $runMode = 'production', array $option = [])
    {
        @ob_end_flush();
        $task = $argv[1]??null;
        if($task === null){
            echo '未指定要执行的任务';
            exit();
        }

        $wd = dirname($_SERVER['PHP_SELF']);
        if(substr($wd, 0, 1) !== DIRECTORY_SEPARATOR){
            $wd = getcwd().'/'.$wd;
        }

        chdir($wd);
        $_SERVER['SCRIPT_NAME']    = $task;
        $_SERVER['REQUEST_METHOD'] = 'cli';
        $_SERVER['HTTP_HOST']      = 'localhost';
        $_SERVER['SERVER_PORT']    = '';
        $_SERVER['REQUEST_URI']    = $task;

        return Bootstrap::boot($runMode, $option);
    }
}