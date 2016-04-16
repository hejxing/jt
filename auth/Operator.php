<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 13:26
 *
 * 对操作人员的封装，可用于权限控制和操作日志记录
 */

namespace jt\auth;


abstract class Operator
{
    /**
     * 操作员类型
     *
     * @type string
     */
    protected $type = null;
    /**
     * 操作员ID
     *
     * @type string|int
     */
    protected $id = null;

    /**
     * 操作员姓名
     *
     * @type string
     */
    protected $name = null;
    /**
     * 操作员职位名称
     *
     * @type string
     */
    protected $position = null;
    /**
     * 扩展信息
     *
     * @type array
     */
    protected $info = null;

    /**
     * Operator constructor.
     *
     * @param string|int $id 操作员ID
     */
    public function __construct($id)
    {
        $this->id = $id;
        $this->initProper();
    }

    abstract function initProper();

    public function getType()
    {
        return $this->type;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getInfo()
    {
        return $this->info;
    }
}