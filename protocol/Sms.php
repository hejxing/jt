<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 11:06
 */

namespace jt\protocol;

use jt\Controller;
use jt\Error;
use jt\Exception;
use jt\utils\Helper;

abstract class Sms
{
    /**
     * 同一个手机号每天发送的最大条数
     *
     * @type int
     */
    protected $maxCount = 5;
    /**
     * 同一个手机号最小的间隔时间
     *
     * @type int
     */
    protected $minMargin = 60;
    /**
     * 发送短信通道
     *
     * @var string
     */
    protected $channel = 'undefined';
    /**
     * 读取短信黑名单的数据库表
     *
     * @type string
     */
    protected $ipBlackModel = '\jt\lib\model\ruler\SmsIpBlack';
    /**
     * 写短信日志的表
     *
     * @type string
     */
    protected $logModel = '\jt\lib\model\log\SmsLogModel';
    /**
     * 接收人清单,手机号码
     *
     * @var array
     */
    protected $receive = [];
    /**
     * 发送成功的列表
     *
     * @type array
     */
    protected $successList = [];
    /**
     * 发送失败的列表
     *
     * @type array
     */
    protected $failList = [];
    /**
     * 短信内容
     *
     * @var string
     */
    protected $content = '';
    /**
     * 短信备注
     *
     * @var string
     */
    protected $remark = '';
    /**
     * 是否需要回执 (尚不清楚回执是否收费)
     *
     * @var bool
     */
    protected $needBack = true;
    /**
     * 当天的发送日志
     *
     * @type array
     */
    protected $todayLog = [];

    /**
     * 短信签名
     *
     * @type string
     */
    protected $signature = '';

    /**
     * 真正发送短信的实现
     *
     * @return mixed
     */
    abstract protected function sending();

    /**
     * 构造函数
     *
     * @param string $remark 备注说明,会写入发送日志
     */
    public function __construct($remark)
    {
        $this->remark  = $remark;
        $classInfo     = explode('\\', get_called_class());
        $this->channel = array_pop($classInfo);
    }


    /**
     * 是否允许该终端发短信
     *
     * @return bool
     */
    protected function clientFilter()
    {
        return true;
    }

    /**
     * 是否允许该收件人收短信发送
     */
    protected function receiverFilter()
    {
        foreach ($this->receive as $index => $mobile) {
            if (empty($this->todayLog[$mobile])) {
                continue;
            }
            if (count($this->todayLog[$mobile]) >= $this->maxCount) {
                unset($this->receive[$index]);
                Error::notice('moreThanMaxSendCount', "号码[{$mobile}]在发送通道[{$this->channel}]中每天最多只允许发送[{$this->maxCount}]条");
            }
            $lastLog = $this->todayLog[$mobile][0];

            if ((time() - strtotime($lastLog['createAt'])) < $this->minMargin) {
                unset($this->receive[$index]);
                Error::notice('sendSmsIntervalTooBrief', "向号码[{$mobile}]发送的短信至少要间隔[{$this->minMargin}]秒");
            }
        }
        if (empty($this->receive)) {
            throw new Exception('sendSmsBlock:禁止发送');
        }
    }

    /**
     * 发送内容过滤
     *
     * @return bool
     */
    protected function contentFilter()
    {
        return true;
    }

    /**
     * 是否需要回执(尚不清楚回执是否收费)
     *
     * @param bool $needBack 是否需要回执
     */
    public function setNeedBack($needBack)
    {
        $this->needBack = $needBack;
    }

    /**
     * 添加接收手机号码,有相同号码,将会被覆盖
     *
     * @param string|array $receive 接收手机号码或列表
     */
    public function addReceiver($receive)
    {
        $receive       = is_array($receive) ? $receive : preg_split('/ *, */', $receive);
        $this->receive = array_unique(array_merge($this->receive, $receive));
    }

    /**
     * 检查短信内容是否有签上名(按政策必须如此)
     */
    protected function supplementarySignature()
    {
        $signature = $this->signature;
        if (!$signature && defined('\Config::SMS_SIGNATURE')) {
            $signature = \Config::SMS_SIGNATURE;
        }
        if ($signature && !preg_match("/^【.+】/", $this->content)) {
            $this->content = '【' . $signature . '】' . $this->content;
        }
    }

    /**
     * 设置短信签名
     *
     * @param $signature
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;
    }

    /**
     * 发送短信
     *
     * @param string       $msg 发送的短信(70个字以内,否则会分成多条发送,一个中文算一个字)
     * @param string|array $receive 接收手机号码
     * @return bool
     */
    public function send($msg, $receive = null)
    {
        $this->content = $msg;
        if ($receive) {
            $this->addReceiver($receive);
        }
        if ($this->verify() === true) {
            $this->supplementarySignature();
            $this->sending();

            return true;
        }

        return false;
    }

    /**
     * 读取当天的发送日志
     */
    protected function readTodayLog()
    {
        /** @type \jt\Model $modelName */
        $modelName = $this->logModel;
        $model     = $modelName::open();
        $today     = date('Y-m-d');
        foreach ($this->receive as $mobile) {
            $this->todayLog[$mobile] = $model->where('createAt > :today', ['today' => $today])
                ->equals('receiver', $mobile)
                ->equals('channel', $this->channel)
                ->equals('status', 'success')
                ->order('createAt', 'desc')->fetch('id, createAt');
        }
    }

    /**
     * 验证是否允许发送
     *
     * @return bool
     * @throws Exception
     */
    private function verify()
    {
        if (count($this->receive) === 0) {
            throw new Exception('receiverSmsListEmpty:短信接收人为空');
        }
        if ($this->content === '') {
            throw new Exception('smsContentEmpty:短信内容为空');
        }

        $this->contentFilter();

        $this->readTodayLog();

        $this->clientFilter();
        $this->receiverFilter();

        return true;
    }

    /**
     * 获取收短信人手机号码列表
     *
     * @return array
     */
    public function getReceiver()
    {
        return $this->receive;
    }

    /**
     * 清空收信手机号码列表
     */
    public function clearReceiver()
    {
        $this->receive = [];
    }

    /**
     * 响应成功事件
     *
     * @param string $mobile
     * @param mixed  $response
     */
    protected function success($mobile, $response)
    {
        $this->writeLog($mobile, $response, 'success');
    }

    /**
     * 响应失败事件
     *
     * @param $mobile
     * @param $response
     */
    protected function fail($mobile, $response)
    {
        $this->writeLog($mobile, $response, 'fail');
    }

    /**
     * 写发送日志
     *
     * @param string $mobile 接收短信的手机号
     * @param string $response 第三方平台响应的信息
     * @param string $status 发送状态  success fail
     * @return array
     */
    private function writeLog($mobile, $response, $status)
    {
        /** @type \jt\Model $modelName */
        $modelName = $this->logModel;
        $model     = $modelName::open();

        $data = array_merge([
            'content'    => $this->content,
            'receiver'   => $mobile,
            'status'     => $status,
            'resultInfo' => Helper::encodeJSON($response),
            'channel'    => $this->channel,
            'remark'     => $this->remark
        ], Controller::current()->getOperator()->fetchAll());

        return $model->add($data);
    }
}