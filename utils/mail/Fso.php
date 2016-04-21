<?php

/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/20 18:26
 */
namespace jt\utils\mail;

use jt\Exception;
use jt\protocol\Email;

class Fso extends Email
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
     * 是否需要经过用户验证
     *
     * @type bool
     */
    private $mailAuth   = false;
    private $authDomain = 'csmall.com';
    private $user       = 'zshejxing@163.com';
    private $password   = 'wskiliwwsk';


    /**
     * 发送前的准备工作
     *
     * @return mixed
     */
    function preSend()
    {
        // TODO: Implement preSend() method.
    }

    /**
     * @param string $type 错语类型
     * @param string $msg 错误描述
     * @throws \jt\Exception
     */
    private function error($type, $msg)
    {
        throw new Exception('SendMailByFsoFail:(' . $this->smtpServer . ':' . $this->smtpPort . ') ' . $type . ' - ' . $msg);
    }

    /**
     * 由各邮件发送方式去具体实现
     *
     * @return mixed
     */
    function sending()
    {
        if (!$fp = fsockopen($this->smtpServer, $this->smtpPort, $no, $err, 1)) {
            $this->error('CONNECT', 'Unable to connect to the SMTP server');
        }
        stream_set_blocking($fp, true);
        stream_set_timeout($fp, 1, 0);
        $message = fgets($fp, 512);
        if (substr($message, 0, 3) != '220') {
            //$this->error('CONNECT', $message);
        }
        fputs($fp, ($this->mailAuth ? 'EHLO' : 'HELO') . " {$this->authDomain}\r\n");
        $message = fgets($fp, 512);
        if (substr($message, 0, 3) != 220 && substr($message, 0, 3) != 250) {
            $this->error('HELO/EHLO', $message);
        }
        while (1) {
            if (substr($message, 3, 1) != '-' || empty($message)) {
                break;
            }
            $message = fgets($fp, 512);
        }
        if ($this->mailAuth) {
            fputs($fp, "AUTH LOGIN\r\n");
            $message = fgets($fp, 512);
            if (substr($message, 0, 3) != 334) {
                $this->error('AUTH LOGIN', $message);
            }

            fputs($fp, base64_encode($this->user) . "\r\n");
            $message = fgets($fp, 512);
            if (substr($message, 0, 3) != 334) {
                $this->error('USERNAME', $message);
            }

            fputs($fp, base64_encode($this->password) . "\r\n");
            $message = fgets($fp, 512);
            if (substr($message, 0, 3) != 235) {
                $this->error('PASSWORD', $message);
            }
        }
        //fputs($fp, "MAIL FROM: <".$this->mailFrom.">\r\n");
        /**
         * 尝试解决
         */
        fputs($fp, "MAIL FROM: ".$this->addressEncode($this->from)."\r\n");
        $message = fgets($fp, 512);
        if (substr($message, 0, 3) != 250) {
            $this->error('MAIL FROM', $message);
        }

        foreach ($this->receive as $to) {
            fputs($fp, "RCPT TO: <" . $to . ">\r\n");
            $message = fgets($fp, 512);
            if (substr($message, 0, 3) != 250) {
                $this->error('RCPT TO', $message);
            }
        }

        fputs($fp, "DATA\r\n");
        $message = fgets($fp, 512);
        if (substr($message, 0, 3) != 354) {
            $this->error('DATA', $message);
        }

        fputs($fp, "Date: " . date('r') . "\r\n");
        //fputs($fp, "To: " . $this->mailTo . "\r\n");
        fputs($fp, 'Subject: ' . $this->encode($this->subject) . "\r\n");
        fputs($fp, $this->header . "\r\n");
        fputs($fp, "\r\n\r\n");
        fputs($fp, $this->body . "\r\n.\r\n");
        fputs($fp, "QUIT\r\n");
    }
}