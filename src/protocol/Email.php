<?php

/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/4/16 11:07
 *
 * 邮件发送类
 */

namespace jt\protocol;

abstract class Email
{
    /**
     * 发送方式
     *
     * @type string
     */
    protected $sendType = '';//发送方式 1 mail() ,2 Fso ,3 smtp
    /**
     * 收件人列表
     *
     * @type array
     */
    protected $receive = []; // 收件人
    /**
     * 显示的发件人
     *
     * @type string
     */
    protected $from = ''; // 发件人
    /**
     * 邮件主题
     *
     * @type string
     */
    protected $subject = ''; // 主题
    /**
     * 邮件头
     *
     * @type string
     */
    protected $header = '';
    /**
     * 邮件内容
     *
     * @type string
     */
    protected $body = '';
    /**
     * 附件
     *
     * @type array
     */
    protected $attachments = []; // 附件
    /**
     * @type string
     */
    protected $delimiter = "\r\n";
    /**
     * 邮件编码
     *
     * @type string
     */
    protected $encoding = 'utf-8';
    /**
     * 邮件内容的格式类型
     *
     * @type string
     */
    protected $contentType = 'text/plain';

    /**
     * 发送前的准备工作
     *
     * @return bool
     */
    abstract function preSend();

    /**
     * 由各邮件发送方式去具体实现
     *
     * @return bool
     */
    abstract function sending();


    /**
     * 添加收件人
     *
     * @param array|string $receive 收件人邮箱或列表
     */
    public function addReceiver($receive)
    {
        $receive       = is_array($receive)? $receive: preg_split('/ *, */', $receive);
        $this->receive = array_unique(array_merge($this->receive, $receive));
    }

    /**
     * 设置发件人的邮箱
     *
     * @param string $from 注意格式一定要符合规范:发件人<发件人邮箱> 示例: 张三<zhangsan@163.com>
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    /**
     * 设置邮件主题
     *
     * @param string $subject 邮件主题（标题）
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * 设置邮件内容
     *
     * @param $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * 设置邮件内容格式
     *
     * @param $mime
     */
    public function setMime($mime)
    {
        $this->contentType = 'text/'.$mime;
    }

    /**
     * 添加附件
     *
     * @param string $file 附件文件地址，多个文件用逗号分隔
     * @return bool
     */
    public function addAttachments($file)
    {
        $files             = is_array($file)? $file: preg_split('/ *, */', $file);
        $this->attachments = array_unique(array_merge($this->attachments, $files));

        return true;
    }

    /**
     * 开始发送
     *
     * @return bool
     */
    public function send()
    {
        $this->fillHeader();
        $this->encodeBody();
        $this->preSend();

        return $this->sending();
    }

    /**
     * 写错误日志，方便调试
     *
     * @param $protocol
     * @param $errorStr
     */
    protected function writeErrorLog($protocol, $errorStr)
    {
        $logFolder = '/server/debug/';
        mkdir($logFolder, 0777, true);
        $mailErrorFile = fopen($logFolder.'customMailLog.txt', 'a+') or die("can't open file");
        fwrite($mailErrorFile, $protocol.':'.$errorStr.'\r');
        fclose($mailErrorFile);
    }

    /**
     * 对邮件内容编码
     *
     * @param string $content
     * @return string
     */
    protected function encode($content)
    {
        return "=?{$this->encoding}?B?".base64_encode($content).'?=';
    }

    /**
     * 对邮件地址进行编码
     *
     * @param $address
     * @return string
     */
    protected function addressEncode($address)
    {
        preg_match('/^(.+?)(<.+>)$/', $address, $match);

        return $this->encode($match[1]).$match[2];
    }

    /**
     * 产生boundary
     *
     * @return string
     */
    protected function getRandomBoundary()
    {
        return uniqid("----PART_");
    }

    /**
     * 根据文件后缀获得附件文件类型
     *
     * @param string $file 文件名
     * @return string
     */
    protected function getContentType($file)
    {
        $extension = strrchr(basename($file), '.');
        switch($extension){
            case ".gif":
                return "image/gif";
            case ".gz":
                return "application/x-gzip";
            case ".htm":
                return "text/html";
            case ".html":
                return "text/html";
            case ".jpg":
                return "image/jpeg";
            case ".tar":
                return "application/x-tar";
            case ".txt":
                return "text/plain";
            case ".zip":
                return "application/zip";
            default:
                return "application/octet-stream";
        }
    }

    /**
     * 获取发件人地址
     *
     * @return string
     */
    protected function getFrom()
    {
        return $this->from?: defined('\Config::MAIL_FROM')? \Config::MAIL_FROM: '';
    }

    protected function fillHeader()
    {
        $delimiter = $this->delimiter;
        //if($this->mailCC != ""){
        //    $header .= "CC: ".$this->mailCC.$delimiter;
        //}
        //if($this->mailBCC != ""){
        //    $header .= "BCC: ".$this->mailBCC.$delimiter;
        //}
        $header = "MIME-Version: 1.0{$delimiter}";
        $header .= "X-Priority: 3{$delimiter}";
        $header .= "X-MSMail-Priority: Normal{$delimiter}";
        $header .= "X-Mailer: csmall.com(copyRight 2017){$delimiter}";
        $header .= "Content-Type: {$this->contentType}; charset={$this->encoding}; format=flowed".$this->delimiter;

        $from = $this->getFrom();
        if($from){
            $header .= "FROM: ".$this->addressEncode($from).$delimiter;
        }
        //$header .= "Content-Transfer-Encoding: {$this->encoding}{$delimiter}";
        $this->header = $header;
    }

    /**
     * 编码邮件主体
     *
     * @return string
     */
    protected function encodeBody()
    {
        if($this->contentType !== 'text/html'){
            return;
        }
        $header = "Content-Transfer-Encoding: base64".$this->delimiter;
        $header .= "Content-Disposition: inline".$this->delimiter.$this->delimiter;
        $header .= chunk_split(base64_encode($this->body)).$this->delimiter;

        $this->header .= $header;
        $this->body   = '';
    }

    /**
     * 编码附件
     *
     * @param string $file 附件文件
     * @return string
     */
    protected function encodeAttachmentHeader($file)
    {
        if(file_exists($file) && $fileStream = file_get_contents($file)){
            $delimiter   = $this->delimiter;
            $header      = "";
            $contentType = $this->getContentType($file);
            $header      .= "Content-Type: {$contentType};{$delimiter}";
            $header      .= "....name=\"".basename($file)."\"{$delimiter}";
            $header      .= "Content-Transfer-Encoding: base64{$delimiter}";
            $header      .= "Content-Disposition: attachment;{$delimiter}";
            $header      .= "....filename=\"".basename($file)."\"{$delimiter}{$delimiter}";

            $header .= chunk_split(base64_encode($fileStream)).$this->delimiter;

            return $header;
        }

        return '';
    }

    /**
     * 处理失败事件
     *
     * @param $mail
     */
    protected function sendFail($mail)
    {
        echo 'Send to '.$mail.' fail';
    }
}