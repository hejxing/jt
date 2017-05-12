<?php
/**
 * @author neeke@php.net
 * Date: 14-1-27 16:47
 */
define('SEASLOG_DEBUG', "debug");
define('SEASLOG_INFO', "info");
define('SEASLOG_NOTICE', "notice");
define('SEASLOG_WARNING', "warning");
define('SEASLOG_ERROR', "error");
define('SEASLOG_CRITICAL', "critical");
define('SEASLOG_ALERT', "alert");
define('SEASLOG_EMERGENCY', "emergency");
define('SEASLOG_DETAIL_ORDER_ASC', 1);
define('SEASLOG_DETAIL_ORDER_DESC', 2);

class SeasLog
{
    public function __construct()
    {
        #SeasLog init
    }

    public function __destruct()
    {
        #SeasLog distroy
    }

    /**
     * Set The basePath
     *
     * @param $basePath
     *
     * @return bool
     */
    static public function setBasePath($basePath)
    {
        return true;
    }

    /**
     * Get The basePath
     *
     * @return string
     */
    static public function getBasePath()
    {
        return 'the base_path';
    }

    /**
     * Set The logger
     *
     * @param $module
     *
     * @return bool
     */
    static public function setLogger($module)
    {
        return true;
    }

    /**
     * Get The lastest logger
     *
     * @return string
     */
    static public function getLastLogger()
    {
        return 'the lastLogger';
    }

    /**
     * Set The DatetimeFormat
     *
     * @param $format
     *
     * @return bool
     */
    static public function setDatetimeFormat($format)
    {
        return true;
    }

    /**
     * Get The DatetimeFormat
     *
     * @return string
     */
    static public function getDatetimeFormat()
    {
        return 'the datetimeFormat';
    }

    /**
     * Count All Types（Or Type）Log Lines
     *
     * @param string $level
     * @param string $log_path
     * @param null   $key_word
     *
     * @return array | long
     */
    static public function analyzerCount($level = 'all', $log_path = '*', $key_word = null)
    {
        return [];
    }

    /**
     * Get The Logs As Array
     *
     * @param        $level
     * @param string $log_path
     * @param null   $key_word
     * @param int    $start
     * @param int    $limit
     * @param        $order SEASLOG_DETAIL_ORDER_ASC(Default)  SEASLOG_DETAIL_ORDER_DESC
     *
     * @return array
     */
    static public function analyzerDetail(
        $level = SEASLOG_INFO,
        $log_path = '*',
        $key_word = null,
        $start = 1,
        $limit = 20,
        $order = SEASLOG_DETAIL_ORDER_ASC
    ){
        return [];
    }

    /**
     * Get The Buffer In Memory As Array
     *
     * @return array
     */
    static public function getBuffer()
    {
        return [];
    }

    /**
     * Flush The Buffer Dump To Writer Appender
     *
     * @return bool
     */
    static public function flushBuffer()
    {
        return true;
    }

    /**
     * Record Debug Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function debug($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_DEBUG
    }

    /**
     * Record Info Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function info($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_INFO
    }

    /**
     * Record Notice Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function notice($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_NOTICE
    }

    /**
     * Record Warning Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function warning($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_WARNING
    }

    /**
     * Record Error Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function error($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_ERROR
    }

    /**
     * Record Critical Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function critical($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_CRITICAL
    }

    /**
     * Record Alert Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function alert($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_ALERT
    }

    /**
     * Record Emergency Log
     *
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function emergency($message, array $content = [], $module = '')
    {
        #$level = SEASLOG_EMERGENCY
    }

    /**
     * The Common Record Log Function
     *
     * @param        $level
     * @param        $message
     * @param array  $content
     * @param string $module
     */
    static public function log($level, $message, array $content = [], $module = '')
    {

    }
}
