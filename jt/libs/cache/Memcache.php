<?php

/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/11/25 11:31
 */
namespace jt\libs\cache;

/**
 * Memcache的轻度封装
 *
 * @package jt\libs\cache
 */
class Memcache{
	/**
	 * 获取值
	 *
	 * @param string $name
	 * @return string
	 */
	public function get($name){
		if($name){

		}

		return '';
	}

	/**
	 * 设置值
	 *
	 * @param string $name
	 * @return bool
	 */
	public function set($name, $value){
		if($name && $value){

		}

		return true;
	}
}