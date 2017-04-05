<?php

/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/4/20 18:27
 */

namespace jt\utils\mail;

use jt\protocol\Email;

class SmtpMail extends Email
{
    /**
     * 邮件服务器地址
     *
     * @type string
     */
    private $smtpServer = 'localhost';
    /**
     * 邮件服务器端口
     *
     * @type string
     */
    private $smtpPort = '25';

    /**
     * 发送前的准备工作
     */
    function preSend()
    {

    }

    /**
     * 由各邮件发送方式去具体实现
     */
    function sending()
    {
        ini_set('SMTP', $this->smtpServer);
        ini_set('smtp_port', $this->smtpPort);
        ini_set('sendmail_from', $this->from);

        foreach($this->receive as $to){
            $res = mail($to, $this->encode($this->subject), $this->body, $this->header);
            if(!$res){
                $this->sendFail($to);
            }
        }
    }
}