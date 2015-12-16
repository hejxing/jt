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
    public function setUp()
    {
        $wd = substr(__DIR__, 0, strrpos(__DIR__, DIRECTORY_SEPARATOR));
        \jt\Bootstrap::init([
            'runMode' => 'develop',
            'docRoot' => $wd
        ]);
    }

    public function testEnvironment()
    {
        $this->assertEquals('develop', RUN_MODE);
        $this->assertEquals('', MODULE);
        $this->assertTrue(class_exists('\Config'));
        $this->assertTrue(class_exists('\jt\Action'));
    }
}
