<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/4/21 17:37
 */

namespace jt\utils\mail;


use PHPUnit\Framework\TestCase;

class FsoMailTest extends TestCase
{
    public function testSend()
    {
        $sender = new FsoMail();
        $sender->addReceiver('ax@csmall.com');
        $sender->setFrom('何渐兴<noReply@csmall.com>');
        $sender->setSubject('测试邮件');
        $sender->setBody('测试邮件内容');
        $sender->send();
    }
}
