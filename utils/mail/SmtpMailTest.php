<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/21 16:39
 */

namespace jt\utils\mail;


class SmtpMailTest extends \PHPUnit_Framework_TestCase
{
    public function testSend(){
        $sender = new SmtpMail();
        $sender->addReceiver('ax@jentian.com');
        $sender->setFrom('noReply<noReply@csmall.com>');
        $sender->setSubject('测试邮件');
        $sender->setBody('测试邮件内容');
        $sender->send();
    }
}
