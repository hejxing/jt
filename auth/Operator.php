<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 13:26
 *
 * 对操作人员的封装，可用于权限控制和操作日志记录
 */

namespace jt\auth;


class Operator
{
    /**
     * 操作员类型
     *
     * @type string
     */
    private $type = null;
    /**
     * 操作员ID
     *
     * @type string|int
     */
    private $id = null;

    /**
     * 操作员姓名
     *
     * @type string
     */
    private $name = null;
    /**
     * 操作员职位名称
     *
     * @type string|int
     */
    private $positionId = '';
    /**
     * 职位名称
     *
     * @type string
     */
    private $positionName = '';
    /**
     * 操作员其它信息
     *
     * @type array
     */
    private $info = [];

    /**
     * 实例化一个操作员
     *
     * @param string     $type 操作员类型
     * @param string|int $id 操作员ID
     * @param string     $name 操作员姓名
     */
    public function __construct($type, $id, $name)
    {
        $this->type = $type;
        $this->id   = $id;
        $this->name = $name;
    }

    /**
     * 设置操作员职位
     *
     * @param $id
     * @param $name
     */
    public function setPosition($id, $name)
    {
        $this->positionId   = $id;
        $this->positionName = $name;
    }

    /**
     * 设置操作的其它信息
     *
     * @param array $info
     */
    public function setInfo(array $info)
    {
        $this->info = $info;
    }

    /**
     * 获取操作员类型
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 获取操作员ID
     *
     * @return int|string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 获取操作员姓名
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取操作员职位ID
     *
     * @return int|string
     */
    public function getPositionId()
    {
        return $this->positionId;
    }

    /**
     * 获取操作员职位名
     *
     * @return string
     */
    public function getPositionName()
    {
        return $this->positionName;
    }

    /**
     * 获取操作信息
     *
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * 获取操作员信息
     *
     * @return array
     */
    public function fetchAll()
    {
        return [
            'operatorType'         => $this->type,
            'operatorId'           => $this->id,
            'operatorName'         => $this->name,
            'operatorPositionId'   => $this->positionId,
            'operatorPositionName' => $this->positionName,
            'operatorInfo'         => $this->info
        ];
    }
}