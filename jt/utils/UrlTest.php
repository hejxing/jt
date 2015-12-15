<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2015/12/10 17:21
 */

namespace jt\utils;


class UrlTest extends \PHPUnit_Framework_TestCase{
	public function testPack(){
		$url      = 'info?pack=1&name=he';
		$packed   = Url::pack($url);
		$unpacked = Url::unpack($packed);
		var_export([$packed, $unpacked]);
		$this->assertEquals($unpacked, $url);
	}
}
