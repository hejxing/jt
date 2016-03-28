<?php
/**
 * Auth: ax@jentian.com
 * Create: 2015/10/17 22:15
 */

/**
 * 测试启动器
 */
class BootstrapTest extends \PHPUnit_Framework_TestCase
{
    public function testEnvironment()
    {
        $this->assertEquals('develop', RUN_MODE);
        $this->assertEquals('app', MODULE);
        $this->assertTrue(class_exists('\Config'));
        $this->assertTrue(class_exists('\jt\Action'));
    }
}
