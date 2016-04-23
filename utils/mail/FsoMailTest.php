<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/21 17:37
 */

namespace jt\utils\mail;


class FsoMailTest extends \PHPUnit_Framework_TestCase
{
    public function testSend(){
        $sender = new FsoMail();
        $sender->addReceiver('ax@jentian.com');
        $sender->setFrom('何渐兴<noReply@csmall.com>');
        $sender->setSubject('测试邮件');
        $sender->setBody('测试邮件内容');
        $sender->send();
    }
}
