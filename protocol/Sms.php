<?php

/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/16 11:06
 */
abstract class Sms
{
    protected $ipBlackTable = 'ruler.sms_ip_black';
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
     * 发送短信通道
     *
     * @var $this
     */
    private $sender = null;
    /**
     * 是否需要回执 (尚不清楚回执是否收费)
     *
     * @var bool
     */
    protected $needBack = true;

    /**
     * 构造函数
     * @param \jt\Operator $operator 操作员
     * @param string $remark 备注说明,会写入发送日志
     */
    public function __construct($operator, $remark)
    {
        $this->operator = $operator;
        $this->remark = $remark;
    }

    /**
     * 寻找合适的短信发送者
     */
    private function findSender()
    {
        if ($this->sender) {
            return;
        }
        if (\in_array($this->type, $this->urgentTypeList)) {
            //$this->sender = new module\YiMei();
            $this->sender = new module\DuanXinTong();
        }else {
            $this->sender = new module\ShangXinTong();
        }
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
     * 设置本次发送短信的类型,类型映射表见枚举类\config\Enum
     *
     * @param int    $type 本次所发送短信的类型
     * @param string $remark
     */
    public function setType($type, $remark = '')
    {
        $this->sender = null;
        $this->type   = $type;
        $this->remark = $remark;
    }

    /**
     * 设置短信内容
     *
     * @param string $msg 短信内容
     */
    public function setMsg($msg)
    {
        $this->msg = $msg;
    }

    /**
     * 添加接收手机号码,有相同号码,将会被覆盖
     *
     * @param string || array $receive 接收手机号码或列表
     */
    public function addReceiver($receive)
    {
        $receive       = is_array($receive) ? $receive : preg_split(" *, *", $receive);
        $this->receive = array_unique(array_merge($this->receive, $receive));
    }

    /**
     * 发送短信
     *
     * @param string||array $receive 接收手机号码
     * @param string $msg 发送的短信(70个字以内,否则会分成多条发送,一个中文算一个字)
     * @return \tools\Res
     */
    public function send($receive = null, $msg = '')
    {
        $ip = \tools\Tools::getIp();
        if (!\pool\Session::get('canBatchSendSms') && in_array($ip, $this->ipBlackList)) {
            $this->res->put('success', false);
            $this->res->put('msg', 'system refuse');
            $this->res->put('code', 1);

            return false;
        }
        $this->findSender();
        if ($receive) {
            $this->addReceiver($receive);
        }
        if ($msg) {
            $this->setMsg($msg);
        }
        if ($this->verify()) {
            $this->sender->send($this->getBody());
            $this->res = $this->sender->getResult();
            $this->addSendLog();
        }

        return $this->res;
    }

    /**
     * 要发送的短信内容
     *
     * @return array
     */
    private function getBody()
    {
        return [
            'receivers' => $this->receive,
            'msg'       => $this->msg,
            'type'      => $this->type,
            'needBack'  => $this->needBack
        ];
    }

    /**
     * 验证是否允许发送
     */
    private function verify()
    {
        if (count($this->receive) === 0) {
            $this->res->put('success', false);
            $this->res->put('msg', 'Error:接收短信名单不能为空 ');
        }
        if ($this->msg === '') {
            $this->res->put('success', false);
            $this->res->put('msg', 'Error:没有设置短信发送内容');
        }

        return $this->res->success();
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
     * 获取发送结果,且体类型有待求证
     *
     * @return array
     */
    public function getResult()
    {
        return $this->res;
    }

    /**
     * 添加新的发送记录
     *
     * @return \tools\Res
     */
    private function addSendLog()
    {
        $smsSendLogManager = \bll\comm\SmsSendLog::create();
        foreach ($this->receive as $receiveMobile) {
            $info = [
                'type'           => $this->type,
                'remark'         => $this->remark,
                'mobile'         => $receiveMobile,
                'content'        => $this->msg,
                'callbackString' => \json_encode($this->res->get()),
                'resultCode'     => $this->res->get('code')
            ];
            $smsSendLogManager->add($info);
        }

        return true;
    }
}