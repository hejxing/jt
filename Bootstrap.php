<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-1
 * Time: 03:25
 * 系统启动初始化工作
 */

namespace jt;

class Bootstrap
{
    /**
     * 系统开始执行时间
     *
     * @type float
     */
    public static $startTime = 0.0;
    /**
     * 系统开始时间
     *
     * @type int
     */
    public static $now = 0;

    /**
     * 加载类
     *
     * @param $className
     * @return bool
     */
    public static function autoLoad($className)
    {
        $classFile = self::getClassFile($className);
        self::loadClass($classFile, $className);
    }

    public static function tryLoad($className)
    {
        $classFile = self::getClassFile($className);
        if (\file_exists($classFile)) {
            return self::loadClass($classFile, $className);
        }else {
            return false;
        }
    }

    private static function loadClass($classFile, $className)
    {
        if (file_exists($classFile)) {
            /** @type mixed $classFile */
            $res = include $classFile;
        }else {
            return false;
        }

        if (method_exists($className, '__init')) {
            $className::__init($className);
        }

        return $res;
    }

    public static function getClassFile($className)
    {
        $prefix = substr($className, 0, strpos($className, '\\'));
        if ($prefix === 'jt') {
            $root = JT_FRAMEWORK_ROOT;
        }else {
            $root = \Config::NAMESPACE_PATH_MAP[$prefix]??PROJECT_ROOT;
        }
        return $root . DIRECTORY_SEPARATOR . \str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
    }

    /**
     * 执行结束后执行的任务
     */
    public static function exeComplete()
    {
        if (Action::isRunComplete() && Action::isSuccess()) {//代码执行 && 业务成功
            if (class_exists('\jt\Model', false)) {
                Model::commit();
            }
        }else {
            if (class_exists('\jt\Model', false)) {
                Model::rollBack();
            }
            $lastError = error_get_last();
            if ($lastError) {
                Error::errorHandler($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
                //短信、邮件通知负责人
            }
        }
    }

    /**
     * 加载配置文件
     */
    private static function loadConfig()
    {
        $configRoot = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'config/';
        require $configRoot . 'Config.php';
        require $configRoot . RUN_MODE . '/Config.php';
    }

    /**
     * 初始化环境
     *
     * @param array $option 环境参数
     */
    private static function init($option)
    {
        umask(0);
        //记录代码执行开始时间
        self::$startTime = microtime(true);
        self::$now       = intval(self::$startTime);

        //定义基本常量
        define('RUN_START_TIME', self::$now);
        define('RUN_MODE', $option['runMode']);

        define('JT_FRAMEWORK_ROOT', substr(__DIR__, 0, -3));

        define('PROJECT_ROOT', getcwd());
        define('MODULE', md5(PROJECT_ROOT));
        define('ERRORS_VERBOSE', RUN_MODE !== 'production');

        self::loadConfig();
        //定义自动加载文件方法
        spl_autoload_register('static::autoLoad');
        //注册错误、异常入口
        ini_set('display_errors', true);
        set_error_handler('\jt\Error::errorHandler');
        set_exception_handler('\jt\Error::exceptionHandler');

        date_default_timezone_set(\Config::TIME_ZONE);
    }

    /**
     * 访问入口
     *
     * @param string $runMode 运行模式
     * @param string $runtimeRoot 存放运行时生成的文件的根目录
     */
    public static function boot($runMode = 'production', $runtimeRoot = '')
    {
        //定义扫尾方法
        register_shutdown_function('\jt\Bootstrap::exeComplete');
        static::init([
            'runMode'     => $runMode,
            'runtimeRoot' => $runtimeRoot
        ]);
        //Debug::log('$_REQUEST', [$_GET, $_POST, $_FILES]);
        //run_before
        Controller::run($_SERVER['SCRIPT_NAME']);
    }
}