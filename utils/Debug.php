<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-1
 * Time: 16:04
 */

namespace jt\utils;

use jt\Action;
use jt\Bootstrap;
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

    public static function runtime($margin = true)
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

        return ($spendTime * 1000);
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
        self::saveToFile('info.log', "{$name} => " . var_export($var, true));
    }

    private static function saveToFile($file, $content)
    {
        $logPath = RUNTIME_PATH_ROOT . '/log';
        if (!file_exists($logPath)) {
            mkdir($logPath, 0777, true);

        }
        $request = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        $request .= "\r\npost: " . \file_get_contents('php://input') . "\r\nget: " . $_SERVER['QUERY_STRING'];
        file_put_contents($logPath . "/$file", "{$request}:\r\n$content\r\n\r\n", FILE_APPEND);
        chmod($logPath."/$file", 0777);
    }

    /**
     * 测试完成后的任务
     */
    public static function complete()
    {
        if (class_exists('\jt\Model', false)) {
            if (self::$isCommit) {//代码执行 && 业务成功
                Model::commit();
            }else {
                Model::rollBack();
            }
        }
        $lastError = error_get_last();
        if ($lastError) {
            echo '---------------------ERROR-----------------', PHP_EOL;
            var_export($lastError);
        }else {
            Action::setIsRunComplete(true);
        }

        echo PHP_EOL, PHP_EOL;
        echo '---------------------RESULT-----------------', PHP_EOL;

        $header         = Error::prepareHeader();
        $header         = array_merge($header, Action::getHeaderStore());
        $header['data'] = Action::getDataStore();
        
        if($header['success']){
            unset($header['success']);
            unset($header['msg']);
        }
        var_export($header);

        echo PHP_EOL, PHP_EOL;
    }

    /**
     * 进入测试入口
     *
     * @param string $root
     * @param string $runtimeRoot
     */
    public static function entrance($root, $runtimeRoot = '')
    {
        require(__DIR__ . '/../Bootstrap.php');
        chdir(__DIR__.'/..');
        //定义扫尾方法
        register_shutdown_function('\jt\utils\Debug::complete');

        Bootstrap::init([
            'runMode' => 'develop',
            'docRoot' => $root,
            'runtimeRoot'  => $runtimeRoot
        ]);
        Error::directOutput();
        $_SERVER['HTTP_USER_AGENT'] = 'Cli/debug';
    }
}