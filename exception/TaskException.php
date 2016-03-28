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
}