<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/21 9:34
 */

namespace jt\utils\mail;


class MailTest extends \PHPUnit_Framework_TestCase
{
    public function testSend(){
        $sender = new Mail();
        $sender->addReceiver('305135667@qq.com');
        $sender->setFrom('noReply<noReply@csmall.com>');
        $sender->setSubject('测试邮件');
        $sender->setBody('测试邮件内容');
        $sender->send();
    }
}
