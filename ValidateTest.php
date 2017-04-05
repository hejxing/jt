<?php
/**
 * Auth: ax@csmall.com
 * Create: 2015/10/17 22:32
 */

namespace jt;

use PHPUnit\Framework\TestCase;

class ValidateTest extends TestCase
{
    public function testEmail()
    {
        $this->assertTrue(Validate::email('ax@csmall.com'));
        $this->assertTrue(Validate::email('ax@csmall.com.cn'));
        $this->assertTrue(Validate::email('123_ax@csmall.com.cn'));
        $this->assertTrue(Validate::email('t123@163.cn'));
        $this->assertFalse(Validate::email('t123@163.cn.'));
        $this->assertFalse(Validate::email('@163.cn'));
        $this->assertFalse(Validate::email('t123@163'));
    }

    public function testMobile()
    {
        $this->assertTrue(Validate::mobile('13066815732'));
        $this->assertTrue(Validate::mobile('130-6681-5732'));
        $this->assertTrue(Validate::mobile('(+86)157-6681-5732'));
        $this->assertTrue(Validate::mobile('(+86)13066815732'));
        $this->assertTrue(Validate::mobile('( +86 )13066815732'));
        $this->assertFalse(Validate::mobile('(+86)130668157321'));
    }

    public function testIdentityCard()
    {
        $this->assertTrue(Validate::identityCard('511324198312076499'));
        $this->assertTrue(Validate::identityCard('362430198906108115'));
    }
}
