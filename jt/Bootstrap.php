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
    public static function loadClass($className)
    {
        $root      = \strpos($className, 'jt') === 0 ? CORE_ROOT : PROJECT_ROOT;
        $classFile = $root . DIRECTORY_SEPARATOR . \str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';

        if (\file_exists($classFile)) {
            require $classFile;
            if (\method_exists($className, '__init')) {
                $className::__init($className);
            }
        }else {
            return false;
        }
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
            $lastError = \error_get_last();
            if ($lastError) {
                Error::errorHandler($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], []);
                //短信、邮件通知负责人
            }
        }
    }

    /**
     * 初始化环境
     *
     * @param array $option 环境参数
     */
    public static function init($option)
    {
        //记录代码执行开始时间
        self::$startTime = microtime(true);
        self::$now       = intval(self::$startTime);

        //入口模块
        //$option['nsRoot'] = 'app';
        $module      = 'app';
        $projectRoot = $option['docRoot'];
        if ($option['nsRoot']) {
            $module      = \str_replace('\\', '_', $option['nsRoot']);
            $projectRoot = \substr($projectRoot, 0, -1 - \strlen($option['nsRoot']));
        }

        //定义基本常量
        define('RUN_START_TIME', self::$now);
        define('RUN_MODE', $option['runMode']);
        define('CORE_ROOT', substr(__DIR__, 0, -3));
        define('PROJECT_ROOT', $projectRoot);
        define('DOCUMENT_ROOT', $option['docRoot']);
        define('MODULE', $module);
        define('MODULE_NAMESPACE_ROOT', $option['nsRoot']);
        //定义自动加载文件方法
        \spl_autoload_register('static::loadClass');
        require PROJECT_ROOT . DIRECTORY_SEPARATOR . \str_replace('\\', DIRECTORY_SEPARATOR,
                MODULE_NAMESPACE_ROOT) . 'config/' . RUN_MODE . '/Config.php';

        //注册错误、异常入口
       
        \set_error_handler('\jt\Error::errorHandler');
        \set_exception_handler('\jt\Error::exceptionHandler');

        \date_default_timezone_set(\Config::TIME_ZONE);
    }

    /**
     * 访问入口
     *
     * @param string $runMode
     */
    public static function boot($runMode = 'production', $nsRoot = '')
    {
        static::init([
            'runMode' => $runMode,
            'docRoot' => \getcwd(),
            'nsRoot'  => $nsRoot
        ]);
        //定义扫尾方法
        \register_shutdown_function('\jt\Bootstrap::exeComplete');
        //run_before
        Controller::run($_SERVER['SCRIPT_NAME']);
    }

    /**
     * 测试入口
     *
     * @param string $root 项目根目录
     */
    public static function test($root, $nsRoot = '')
    {
        static::init([
            'runMode' => 'develop',
            'docRoot' => $root,
            'nsRoot'  => $nsRoot
        ]);
    }

    /**
     * 启动会话
     */
    public static function sessionStart(){
        $handlerName = \Config::SESSION_HANDLER;
        \session_set_save_handler(new $handlerName());
        \session_register_shutdown();
        \session_start();
    }
}