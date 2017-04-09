<?php
/**
 * User: 渐兴
 * Date: 15-4-1 03:25
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
     * 执行结束后执行的任务
     */
    public static function exeComplete()
    {
        if (Controller::current()->isCompleteAndSuccess()) {//代码执行 && 业务成功
            if (class_exists('\jt\Model', false)) {
                Model::commitAll();
            }
        }else {
            if (class_exists('\jt\Model', false)) {
                Model::rollBack();
            }
            $lastError = error_get_last();
            if ($lastError) {
                Error::logFatal('FatalError: '.$lastError['type'],
                    $lastError['message'].' in '.$lastError['file'].' line '.$lastError['line']);
                //短信、邮件通知负责人
            }
        }
    }

    /**
     * 加载配置文件
     */
    private static function loadConfig()
    {
        $file = RUNTIME_PATH_ROOT.'/config/'.MODULE.'.php';
        if (!is_file($file) || RUN_MODE === 'develop') { //解析生成配置文件
            compile\config\Config::general($file, PROJECT_ROOT.'/config/'.RUN_MODE.'/Config.php');
        }
        /** @noinspection PhpIncludeInspection */
        include($file);
    }

    private static function defineEnvironment($option)
    {
        $option['projectRoot'] = $option['projectRoot']??'';
        $option['runtimePath'] = $option['runtimePath']??'runtime';

        if (substr($option['projectRoot'], 0, 1) !== '/') {
            $option['projectRoot'] = getcwd().($option['projectRoot']? '/'.$option['projectRoot']: '');
        }
        if (substr($option['runtimePath'], 0, 1) !== '/') {
            $option['runtimePath'] = $option['projectRoot'].'/'.$option['runtimePath'];
        }

        $moduleName = $option['moduleName']??substr(strrchr($option['projectRoot'], '/'), 1);
        //定义基本常量
        define('RUN_START_TIME', self::$now);
        define('RUN_MODE', $option['runMode']);
        define('PROJECT_ROOT', $option['projectRoot']);
        define('RUNTIME_PATH_ROOT', $option['runtimePath']);
        define('MODULE', $moduleName);
        define('ERRORS_VERBOSE', RUN_MODE !== 'production');
    }

    /**
     * 初始化环境
     *
     * @param string $runMode 运行模式
     * @param array  $option 环境参数
     */
    public static function init($runMode, $option)
    {
        //记录代码执行开始时间
        self::$startTime = microtime(true);
        self::$now       = intval(self::$startTime);
        ini_set('display_errors', true);

        $option['runMode'] = $runMode;
        self::defineEnvironment($option);
        self::loadConfig();
        //定义扫尾方法
        register_shutdown_function('\jt\Bootstrap::exeComplete');
        //注册错误、异常入口
        set_error_handler('\jt\Error::errorHandler');
        set_exception_handler('\jt\Error::exceptionHandler');

        date_default_timezone_set(\Config::TIME_ZONE);
    }

    /**
     * 访问入口
     *
     * @param string $runMode 运行模式
     * @param array  $option 选项
     *   projectRoot 本项目所在的根目录，命名空间的根目录所在的目录为准
     *   runtimeRoot 存放运行时生成的文件的根目录
     *
     * @return Controller
     */
    public static function boot($runMode = 'production', array $option = [])
    {
        static::init($runMode, $option);
        //对S_SERVER中的输入内容进行安全处理
        Requester::safeHeader();

        return new Controller($_SERVER);
    }
}