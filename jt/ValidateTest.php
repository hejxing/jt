<?php
/**
 * Auth: ax@jentian.com
 * Create: 2015/10/17 22:32
 */
namespace jt;

class ValidateTest extends \PHPUnit_Framework_TestCase
{
    public function testEmail()
    {
        $this->assertTrue(Validate::email('ax@jentian.com'));
        $this->assertTrue(Validate::email('ax@jentian.com.cn'));
        $this->assertTrue(Validate::email('123_ax@jentian.com.cn'));
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
        $this->assertFalse(Validate::mobile('(+86)130668157321'));
        $this->assertFalse(Validate::mobile('(+86)130668157321'));
        $this->assertFalse(Validate::mobile('(+86)130668157321'));
    }
}
