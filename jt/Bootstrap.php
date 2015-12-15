<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-1
 * Time: 03:25
 * 系统启动初始化工作
 */

namespace jt;

class Bootstrap{
	/**
	 * 系统开始执行时间
	 * @type float
	 */
	public static $startTime = 0.0;
	/**
	 * 系统开始时间
	 * @type int
	 */
	public static $now = 0;

	/**
	 * 加载类
	 * @param $className
	 * @return bool
	 */
	public static function loadClass($className){
		$classFile = CORE_ROOT . DIRECTORY_SEPARATOR . \str_replace('\\', DIRECTORY_SEPARATOR, $className). '.php';
		//$isConfig  = false;
		if(\strpos($className, '\Config')){
			//$isConfig = true;
			//if(\strpos($className, 'app') !== 0){//非配置文件
			//	return false;
			//}
			$configDir = 'config'.DIRECTORY_SEPARATOR .RUN_MODE. DIRECTORY_SEPARATOR;
			if(\strpos($className, '\database\Config')){
				$classFile = \substr($classFile, 0, -19).$configDir.'Database.php';
			}else{
				$classFile = \substr($classFile, 0, -10).$configDir.'Config.php';
			}
		}

		if(\file_exists($classFile)){
			require $classFile;
		}else{
			//if($isConfig){
			//	Error::fatal('404', '配置文件：' . $classFile . ' 不存在');
			//}else{
				return false;
			//}
		}
//等到了PHP7再使用以下方法
//		try{
//			include $classFile;
//		}catch (\Exception $e){
//			if($isConfig){
//				Error::fatal('404', '配置文件：' . $classFile . ' 不存在');
//			}else{
//				return false;
//			}
//		}
		if(\method_exists($className, '__init')){
			$className::__init($className);
		}
	}

	/**
	 * 执行结束后执行的任务
	 */
	public static function exeComplete(){
		if (Action::isRunComplete() && Action::isSuccess()){//代码执行 && 业务成功
			Model::commit();
		}else{
			Model::rollBack();
			$lastError = \error_get_last();
			if($lastError){
				Error::errorHandler($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line'], []);
				//短信、邮件通知负责人
			}
		}
	}

	/**
	 * 初始化环境
	 * @param array $option 环境参数
	 */
	public static function init($option){
		//记录代码执行开始时间
		self::$startTime = microtime(true);
		self::$now = intval(self::$startTime);

		//入口模块
		$module = '';
		if(\strpos($option['docRoot'], DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR) > 0){
			list(, $module) = \explode(DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR, $option['docRoot']);
		}

		//定义基本常量
		define('RUN_START_TIME', self::$now);
		define('RUN_MODE', $option['runMode']);
		define('CORE_ROOT', substr(__DIR__, 0, -3));
		define('DOCUMENT_ROOT', $option['docRoot']);
		define('MODULE', $module);
		define('MODULE_NAMESPACE_ROOT', 'app\\' . $module);

		//定义自动加载文件方法
		\spl_autoload_register('static::loadClass');

		//注册错误、异常入口
		\ini_set("display_errors", "1");
		//\set_error_handler('\jt\Error::errorHandler', E_ALL | E_STRICT);
		//\set_exception_handler('\jt\Error::exceptionHandler');

		\class_alias(MODULE?'app\\'.MODULE.'\Config':'\\config\\Base', '\Config');

		\date_default_timezone_set(\Config::TIME_ZONE);
	}

	/**
	 * 访问入口
	 * @param string $runMode
	 */
	public static function boot($runMode = 'production'){
		static::init([
			'runMode' => $runMode,
			'docRoot' => \getcwd()
		]);
		//定义扫尾方法
		\register_shutdown_function('\jt\Bootstrap::exeComplete');
		//run_before
		Controller::run($_SERVER['SCRIPT_NAME']);
	}

	/**
	 * 测试入口
	 * @param string $root 项目根目录
	 */
	public static function test($root){
		static::init([
				'runMode' => 'develop',
				'docRoot' => $root
		]);
	}
}