<?php
/**
 * Auth: ax@csmall.com
 * Create: 2015/10/17 22:15
 */

namespace jt;

use PHPUnit\Framework\TestCase;

/**
 * 测试启动器
 */
class BootstrapTest extends TestCase
{
    public function testEnvironment()
    {
        $this->assertEquals('develop', RUN_MODE);
        $this->assertEquals('jt', MODULE);
        $this->assertTrue(class_exists('\Config'));
        $this->assertTrue(class_exists('\jt\Action'));
    }
}
