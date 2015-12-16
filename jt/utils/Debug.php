<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-1
 * Time: 16:04
 */

namespace jt\utils;


class Debug
{
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
            self::$lastTime = \jt\Bootstrap::$startTime;
        }
        if ($margin) {
            $spendTime = $now - self::$lastTime;
        }else {
            $spendTime = $now - \jt\Bootstrap::$startTime;
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
        if (!file_exists(\Config::LOG_PATH_ROOT)) {
            mkdir(\Config::LOG_PATH_ROOT, 0777, true);
        }
        $request = $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        $request .= "\r\npost: " . \file_get_contents('php://input') . "\r\nget: " . $_SERVER['QUERY_STRING'];
        file_put_contents(\Config::LOG_PATH_ROOT . "/$file", "{$request}:\r\n$content\r\n\r\n", FILE_APPEND);
    }
}