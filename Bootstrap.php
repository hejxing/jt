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
     * 加载类
     *
     * @param $className
     * @return mixed
     */
    public static function autoLoad($className)
    {
        $classFile = self::getClassFile($className);

        return self::loadClass($classFile, $className);
    }

    /**
     * 加载类，加载前会判断文件是否存在
     *
     * @param $className
     * @return bool|mixed
     */
    public static function tryLoad($className)
    {
        $classFile = self::getClassFile($className);
        if(file_exists($classFile)){
            return self::loadClass($classFile, $className);
        }else{
            return false;
        }
    }

    private static function loadClass($classFile, $className)
    {
        if(file_exists($classFile)){
            /** @noinspection PhpIncludeInspection */
            /** @type mixed $classFile */
            $res = require $classFile;
        }else{
            return false;
        }

        if(method_exists($className, '__init')){
            $className::__init($className);
        }

        return $res;
    }

    /**
     * @param $className
     * @return string
     */
    public static function getClassFile($className)
    {
        $prefix = substr($className, 0, strpos($className, '\\'));
        if($prefix === 'jt'){
            $root = JT_FRAMEWORK_ROOT;
        }elseif(isset((\Config::NAMESPACE_PATH_MAP)[$prefix])){
            $root = \Config::NAMESPACE_PATH_MAP[$prefix];
        }else{
            $root = \Config::NAMESPACE_ROOT;
        }

        return $root.DIRECTORY_SEPARATOR.\str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
    }

    /**
     * 执行结束后执行的任务
     */
    public static function exeComplete()
    {
        if(Controller::current()->isCompleteAndSuccess()){//代码执行 && 业务成功
            if(class_exists('\jt\Model', false)){
                Model::commitAll();
            }
        }else{
            if(class_exists('\jt\Model', false)){
                Model::rollBack();
            }
            $lastError = error_get_last();
            if($lastError){
                Error::logFatal('FatalError: '.$lastError['type'], $lastError['message'].' in '.$lastError['file'].' line '.$lastError['line']);
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
        if(RUN_MODE === 'develop' || !file_exists($file)){ //解析生成配置文件
            compile\config\Config::general($file, PROJECT_ROOT.'/config/'.RUN_MODE.'/Config.php');
        }
        /** @noinspection PhpIncludeInspection */
        include($file);
    }

    /**
     * 初始化环境
     *
     * @param string $runMode 运行模式
     * @param array  $option 环境参数
     */
    public static function init($runMode, $option)
    {
        $option = [
            'runMode'     => $runMode,
            'projectRoot' => $option['projectRoot']??'',
            'runtimePath' => $option['runtimePath']??'runtime'
        ];
        //定义扫尾方法
        register_shutdown_function('\jt\Bootstrap::exeComplete');
        //记录代码执行开始时间
        self::$startTime = microtime(true);
        self::$now       = intval(self::$startTime);

        if(substr($option['projectRoot'], 0, 1) !== '/'){
            $option['projectRoot'] = getcwd().($option['projectRoot']? '/'.$option['projectRoot']: '');
        }
        if(substr($option['runtimePath'], 0, 1) !== '/'){
            $option['runtimePath'] = $option['projectRoot'].'/'.$option['runtimePath'];
        }

        //定义基本常量
        define('RUN_START_TIME', self::$now);
        define('RUN_MODE', $option['runMode']);

        define('JT_FRAMEWORK_ROOT', substr(__DIR__, 0, -3));

        define('PROJECT_ROOT', $option['projectRoot']);
        define('RUNTIME_PATH_ROOT', $option['runtimePath']);
        define('MODULE', implode('_', array_slice(explode('/', PROJECT_ROOT), -2)));
        define('ERRORS_VERBOSE', RUN_MODE !== 'production');

        //定义自动加载文件方法
        spl_autoload_register('static::autoLoad');
        self::loadConfig();
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