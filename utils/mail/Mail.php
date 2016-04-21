<?php

/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/20 18:26
 */
namespace jt\utils\mail;

use jt\protocol\Email;

class Mail extends Email
{

    /**
     * 发送前的准备工作,检查是否可以发送
     *
     * @return mixed
     */
    function preSend()
    {

    }

    /**
     * 由各邮件发送方式去具体实现
     *
     * @return mixed
     */
    function sending()
    {
        foreach ($this->receive as $to){
            $res = mail($to, $this->encode($this->subject), $this->body, $this->header);
            if(!$res){
                $this->sendFail($to);
            }
        }
    }
}