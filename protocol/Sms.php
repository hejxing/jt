<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 11:06
 */

namespace jt\protocol;

use jt\Controller;
use jt\Exception;

abstract class Sms
{
    /**
     * 发送短信通道
     *
     * @var string
     */
    protected $sender = 'undefined';
    /**
     * 读取短信黑名单的数据库表
     *
     * @type string
     */
    protected $ipBlackTable = 'ruler.sms_ip_black';
    /**
     * 写短信日志的表
     *
     * @type string
     */
    protected $logModel = '/jt/lib/model/log/SmsLogModel';
    /**
     * 接收人清单,手机号码
     *
     * @var array
     */
    protected $receive = [];
    /**
     * 短信内容
     *
     * @var string
     */
    protected $msg = '';
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
        $this->remark = $remark;
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
     *
     * @return bool
     */
    protected function receiverFilter()
    {
        return true;
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
     * 发送短信
     *
     * @param string       $msg 发送的短信(70个字以内,否则会分成多条发送,一个中文算一个字)
     * @param string|array $receive 接收手机号码
     * @return bool
     */
    public function send($msg, $receive = null)
    {
        $this->msg = $msg;
        if ($receive) {
            $this->addReceiver($receive);
        }
        if ($this->verify() === true) {
            $this->sending();
            $this->writeLog();

            return true;
        }

        return false;
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
        if ($this->msg === '') {
            throw new Exception('smsContentEmpty:短信内容为空');
        }

        if ($this->contentFilter() !== true) {
            return false;
        }

        if ($this->clientFilter() !== true) {
            return false;
        }
        if ($this->receiverFilter() !== true) {
            return false;
        }

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
     * 写发送日志
     *
     * @return bool
     */
    private function writeLog()
    {
        /** @type \jt\Model $modelName */
        $modelName = $this->logModel;
        $model     = $modelName::open();
        $model->add(array_merge([
            'content'  => $this->msg,
            'receiver' => $this->receive,
            'sender'   => $this->sender
        ], Controller::current()->getOperator()->fetchAll()));
    }
}