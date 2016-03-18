<?php
/**
 * 文档自动生成
 */
namespace jt\maker\docs;

use jt\Action;
use jt\lib\markdown\michelf\Markdown;
use jt\Responder;
use jt\Template;

class Docs extends Action{
	public function fetch($url){
		$this->setMime('html');
		Responder::setTplEngine(new Template([
			'pathRoot' => __DIR__.'/tpl',
			'left_delimiter' => '{{',
			'right_delimiter' => '}}'
		]));

		$this->prepareClassList();

		switch(true){
		case $url === 'index':
			$this->projectIndex();
			break;
		case preg_match('/^package(.*)/', $url, $matched) > 0:
			if(substr($matched[1],-6) === '/index'){
				$this->status(404);
			}
			$this->packageDetail($matched[1]);
			break;
		case preg_match('/^class(\/.*)/', $url, $matched) > 0:
			$this->classDetail($matched[1]);
			break;
		default:
			$this->status(404);
		}
	}

	private function prepareClassList(){
		
	}

	/**
	 * 首页，项目介绍
	 */
	private function projectIndex(){
		$this->out('projectDesc', $this->readREADME('README.md'));
		$this->setTpl('index');
	}
	private function packageDetail($name){
		$this->out('packageDesc', $this->readREADME('action'.$name.'/README.md'));
		$this->setTpl('packageDetail');
	}

	private function classDetail($name){
		$this->setTpl('classDetail');
	}

	private function readREADME($file){
		$markerDown = new Markdown();
		return $markerDown->defaultTransform(file_get_contents(DOCUMENT_ROOT.'/'.$file));
	}
}