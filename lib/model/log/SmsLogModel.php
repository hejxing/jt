<?php

/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 16:19
 */
namespace jt\lib\model\log;

use jt\Model;
use jt\utils\Helper;

class SmsLogModel extends Model
{
    protected        $conn    = 'log';
    protected        $table   = 'log.sms_send';
    protected static $columns = [
        'id'                   => 'int4 increment primary',
        'operatorType'         => 'field:operator_type varchar:24',
        'operatorId'           => 'field:operator_id varchar:36',
        'operatorName'         => 'field:operator_name varchar:24',
        'operatorPositionId'   => 'field:operator_position_id varchar:36',
        'operatorPositionName' => 'field:operator_position_name varchar:24',
        'operatorInfo'         => 'field:operator_info array varchar:1024',
        'content'              => 'varchar:1024',
        'receiver'             => 'varchar:24',
        'channel'              => 'varchar:32',//短信发送通道
        'remark'               => 'varchar:255',//发送调用者备注,由开发人员提供
        'status'               => 'varchar:16',//发送结果
        'resultInfo'           => 'text field:result_info',//平台返回的信息
        'ip'                   => 'varchar:32 stuffer:getIp',
        'agent'                => 'text stuffer:getAgent',
        'createAt'             => 'field:create_at timestamp at:create'
    ];

    /**
     * 获取默认的Ip
     *
     * @return string
     */
    public function getIp()
    {
        return Helper::getIp();
    }

    /**
     * 获取默认的客户端信息
     *
     * @return mixed
     */
    public function getAgent()
    {
        return $_SERVER['HTTP_USER_AGENT']?:'';
    }
}