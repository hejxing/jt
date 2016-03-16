<?php
/**
 * 文档自动生成
 */
namespace jt\maker\docs;

use jt\Action;

class Docs extends Action{
	public function fetch($url){
		$this->quiet();
		require __DIR__.'/tpl.php';
	}
}