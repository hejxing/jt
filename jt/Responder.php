<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 19:04
 */

namespace jt;

use jt\utils\Debug;

/**
 * 给客户端输出请求结果
 *
 * @package jt
 */

class Responder{
	/**
	 * 模板中所用到插件 工具
	 *
	 * @type array
	 */
	private $tplPlugins = [];

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
		$tpl = new Template();
		foreach(\Config::TPL_PLUGINS as $name => $class){
			$tpl->assignGlobal($name, new $class());
		}

		return $tpl->render(\Config::TPL_PATH_ROOT . Controller::current()->getTemplate(), Action::getDataStore());
	}


	/**
	 * 将数组转为XML
	 *
	 * @param $array
	 * @param $xml
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
		if(RUN_MODE !== 'production'){
			Debug::output($content);
		}
		//拦截
		echo $content;
	}
}