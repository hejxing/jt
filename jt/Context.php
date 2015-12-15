<?php
/**
 * Created by ax@jentian.com.
 * Date: 2015/7/4 11:19
 * 
 * 
 */

namespace jt;


class Context {
	/**
	 * 当前线程ID
	 * @type string
	 */
	private static $currentThreadId = '';
	/**
	 * 线程池
	 * @type array
	 */
	private static $threadPool = [];

	/**
	 * 切换到指定线程
	 * @param $threadId
	 */
	public static function switchTo($threadId){
		self::$currentThreadId = $threadId;
	}

	/**
	 * 将某对象保存到当前进程
	 * @param $name
	 * @param $value
	 */
	public static function __set($name, $value){
		self::$threadPool[self::$currentThreadId][$name] = $value;
	}

	/**
	 * 取出当前进程下的某对象
	 * @param $name
	 * @return mixed
	 */
	public static function __get($name){
		return self::$threadPool[self::$currentThreadId][$name];
	}
}