<?php

/**
 * @Copyright jentian.com
 * Auth: hejxing
 * Create: 2015/12/7 14:39
 */
namespace jt\exception;

/**
 * 因业务逻辑出错导致的异常
 *
 * @package jt\exception
 */
class TaskException extends \Exception
{
    protected $type = 'task';
    protected $data = [];
    protected $param = [];

    /**
     * 设置错误类型
     * @param $type
     */
    public function setType($type){
        $this->type = $type;
    }

    /**
     * 获取错误类型
     * @return string
     */
    public function getType(){
        return $this->type;
    }

    /**
     * 添加附加数据
     * @param array $data
     */
    public function addData(array $data){
        $this->data = array_merge($this->data, $data);
    }

    /**
     * 获取附加数据
     * @return array
     */
    public function getData(){
        return $this->data;
    }
    /**
     * 添加附加参数 不能为string keys
     * @param array $param
     */
    public function setParam(array $param){
        $this->param = array_merge($this->param, $param);
    }

    /**
     * 获取参数，该参数可以传递给自定义的错误处理方法
     * @return array
     */
    public function getParam(){
        return $this->param;
    }
}