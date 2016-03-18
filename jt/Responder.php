<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 19:04
 */

namespace jt;

use jt\exception\TaskException;

/**
 * 给客户端输出请求结果
 *
 * @package jt
 */
class Responder{
	/**
	 * 解析html用到的模板引擎
	 *
	 * @type null
	 */
	protected static $tplEngine = null;

	/**
	 * 生成内容
	 *
	 * @return string
	 */
	protected static function render(){
		switch(Controller::current()->getMime()){
		case 'json':
			return self::json();
		case 'html':

			return self::html();
		case 'xml':
			return self::xml();
		}
	}

	/**
	 * 以json响应客户端
	 */
	protected static function json(){
		\header('Content-type: application/json; charset=' . \Config::CHARSET);
		$header         = Error::prepareHeader();
		$header         = array_merge($header, Action::getHeaderStore());
		$header['data'] = Action::getDataStore();

		$content = \json_encode($header, \Config::JSON_FORMAT);

		return $content;
	}

	/**
	 * 输出HTML
	 */
	protected static function html(){
		\header('Content-type: text/html; charset=' . \Config::CHARSET);
		$tpl = self::$tplEngine ?: new Template([
			'pathRoot'        => \Config::TPL_PATH_ROOT,
			'plugins'         => \Config::TPL_PLUGINS,
			'caching'         => \Config::TPL_CACHING,
			'debugging'       => \Config::TPL_DEBUGGING,
			'force_compile'   => \Config::TPL_FORCE_COMPILE,
			'cache_lifetime'  => \Config::TPL_CACHE_LIFETIME,
			'left_delimiter'  => \Config::TPL_LEFT_DELIMITER,
			'right_delimiter' => \Config::TPL_RIGHT_DELIMITER,
			'baseData'        => \Config::$webDefaultData
		]);
		
		$content = $tpl->render(Controller::current()->getTemplate(), Action::getDataStore());
		if(RUN_MODE !== 'production'){
			//Debug::output($content);
			$hData = Error::prepareHeader();
			foreach(['fatal', 'notice', 'info'] as $type){
				if(isset($hData[$type])){
					echo '<b>' . $type . '</b><br>';
					var_export($hData[$type]);
				}
			}
		}
		return $content;
	}


	/**
	 * 将数组转为XML
	 *
	 * @param                   $array
	 * @param \SimpleXMLElement $xml
	 *
	 * @return mixed
	 */
	private static function array2xml($array, $xml){
		foreach($array as $key => $value){
			if(is_numeric($key)){
				$key = 'item';
			}
			if(is_array($value)){
				self::array2xml($value, $xml->addChild($key));
			}else{
				$xml->addChild($key, \htmlspecialchars($value));
			}
		}

		return $xml;
	}

	/**
	 * 输出xml
	 */
	protected static function xml(){
		\header('Content-type: application/xml; charset=' . \Config::CHARSET);
		$header         = Error::prepareHeader();
		$header         = array_merge($header, Action::getHeaderStore());
		$header['data'] = Action::getDataStore();
		$content        = self::array2xml($header, new \SimpleXMLElement('<root></root>'))->asXML();

		return $content;
	}

	/**
	 * 输出结果
	 */
	public static function write(){
		$content = static::render();
		//拦截
		echo $content;
	}

	/**
	 * 跳转到指定地址
	 *
	 * @param     $url
	 * @param int $status
	 */
	public static function redirect($url, $status = 302){
		header('Location:' . $url, true, $status);
		self::end($status);
	}

	/**
	 * 结束本次请求
	 *
	 * @param int $status
	 * @throws \jt\exception\TaskException
	 */
	public static function end($status = 200){
		if($status){
			\header('Status: ' . $status, true);
		}
		
		throw new TaskException('__USER_END_THE_TASK__');
	}

	/**
	 * 设置解析HTML用到的模板引擎
	 *
	 * @param $engine
	 */
	public static function setTplEngine($engine){
		static::$tplEngine = $engine;
	}
}