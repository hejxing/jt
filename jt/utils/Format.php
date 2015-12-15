<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/6/4
 * Time: 15:37
 */

namespace jt\utils;


class Format {
	/**
	 * 格式化金额值
	 * @param float $n 金额
	 * @return float 保留两位精度的浮点数
	 */
	public static function money($n){
		return sprintf("%01.2f", is_numeric($n) ? $n : 0);
	}
}