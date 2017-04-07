<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/4/21 16:39
 */

namespace jt\utils\mail;


use PHPUnit\Framework\TestCase;

class SmtpMailTest extends TestCase
{
    public function testSend()
    {
        $sender = new SmtpMail();
        $sender->addReceiver('ax@csmall.com');
        $sender->setFrom('noReply<noReply@csmall.com>');
        $sender->setSubject('测试邮件');
        $sender->setBody('测试邮件内容');
        $sender->send();
    }
}
