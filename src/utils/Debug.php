<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-1
 * Time: 16:04
 */

namespace jt\utils;

use jt\Bootstrap;
use jt\Controller;
use jt\Error;
use jt\Model;

class Debug
{
    /**
     * 是否提交对数据库的操作
     *
     * @type bool
     */
    private static $isCommit = true;
    /**
     * 上一次调用的时间(ms)
     *
     * @param bool $margin
     * @return mixed
     */
    private static $lastTime = null;

    /**
     * @var array 搜集各种调试信息
     */
    protected static $collect = [];

    public static function runtime($print = false, $margin = true)
    {
        $now = microtime(true);
        if (self::$lastTime === null) {
            self::$lastTime = Bootstrap::$startTime;
        }
        if ($margin) {
            $spendTime = $now - self::$lastTime;
        }else {
            $spendTime = $now - Bootstrap::$startTime;
        }
        self::$lastTime = $now;
        $spendTime = $spendTime * 1000;

        if($print){
            echo $spendTime . ' ms', PHP_EOL;
        }

        return $spendTime;
    }

    /**
     * 记录输出内容
     *
     * @param $content
     */
    public static function output($content)
    {
        self::saveToFile('output.log', $content);
    }

    /**
     * 错误处理
     *
     * @param $content
     */
    public static function error($content)
    {
        self::saveToFile('error.log', $content);
    }

    /**
     * 捕获请求内容
     *
     * @param $content
     */
    public static function request($content)
    {
        self::saveToFile('request.log', $content);
    }

    /**
     * 输出变量详细信息
     *
     * @param      $var
     * @param bool $break
     */
    public static function dump($var, $break = true)
    {
        echo '<pre>';
        var_export($var);
        echo '</pre>';
        if ($break) {
            exit();
        }
    }

    /**
     * 输出调试日志
     *
     * @param $name
     * @param $var
     */
    public static function log($name, $var)
    {
        self::saveToFile('info.log', "{$name} => ".var_export($var, true));
    }

    private static function saveToFile($file, $content)
    {
        $logPath = \Config::DEBUG_LOG_DIR;
        if (!file_exists($logPath)) {
            mkdir($logPath, 0777, true);

        }
        $request = $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
        $request .= "\r\n"."post: ".\file_get_contents('php://input')."\r\n"."get: ".$_SERVER['QUERY_STRING'];
        file_put_contents($logPath."/$file", "{$request}:\r\n$content\r\n\r\n", FILE_APPEND);
        chmod($logPath."/$file", 0777);
    }

    public static function trace()
    {
        echo '<pre>';
        debug_print_backtrace();
        echo '</pre>';
    }

    /**
     * 测试完成后的任务
     */
    public static function complete()
    {
        if (class_exists('\jt\Model', false)) {
            if (self::$isCommit) {//代码执行 && 业务成功
                Model::commitAll();
            }else {
                Model::rollBackAll();
            }
        }
        $lastError = error_get_last();
        $action    = Controller::current()->getAction();
        if ($lastError) {
            echo '---------------------ERROR-----------------', PHP_EOL;
            var_export($lastError);
        }else {
            $action->setIsRunComplete(true);
        }

        echo PHP_EOL, PHP_EOL;
        echo '---------------------RESULT-----------------', PHP_EOL;

        $header         = Error::prepareHeader();
        $header         = array_merge($header, $action->getHeaderStore());
        $header['data'] = $action->getDataStore();

        var_export($header);

        echo PHP_EOL, PHP_EOL;
    }

    /**
     * 搜集调试信息
     *
     * @param $name
     * @param $content
     */
    public static function collect($name, $content)
    {
        self::$collect[$name][] = $content;
    }

    /**
     * 获取搜集到的调试信息
     *
     * @param $name
     * @return array
     */
    public static function getFromCollect($name)
    {
        return self::$collect[$name]??[];
    }

    /**
     * 进入测试入口
     *
     * @param string $runtimePath 项目运行时生成的目录
     * @param string $projectRoot 项目根目录
     */
    public static function entrance($runtimePath = '', $projectRoot = null)
    {
        if ($_SERVER['argv'][4] === '--filter') {
            $testClass = $_SERVER['argv'][6];
            $testFile  = $_SERVER['argv'][7];
        }else {
            $testClass = $_SERVER['argv'][4];
            $testFile  = $_SERVER['argv'][5];
        }

        if ($projectRoot === null) {
            $testClass = strstr($testClass, '\\');
            $projectRoot = explode(str_replace('\\', DIRECTORY_SEPARATOR, $testClass), $testFile)[0];
        }

        require(__DIR__.'/../Bootstrap.php');
        //定义扫尾方法
        register_shutdown_function('\jt\utils\Debug::complete');
        Bootstrap::init('develop', [
            'projectRoot' => $projectRoot,
            'runtimePath' => $runtimePath,
            'moduleName'  => 'jt_framework'
        ]);
        Error::directOutput();
        $_SERVER['HTTP_USER_AGENT'] = 'Cli/debug';
        $_SERVER['SERVER_PORT']     = '--';
        $_SERVER['HTTP_HOST']       = 'cli';
        $_SERVER['REMOTE_ADDR']     = '127.0.0.1';
        $_SERVER['SCRIPT_NAME']     = $testFile;
        $_SERVER['REQUEST_METHOD']  = 'CLI';
    }
}