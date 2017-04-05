<?php
/**
 * Auth: ax
 * Date: 2017/3/26 15:50
 */

namespace jt\utils;

use PHPUnit\Framework\TestCase;

class HtmlPurifierTest extends TestCase
{
    public function testPurifier()
    {
        $purifier  = new HtmlPurifier(null);
        $dirtyHtml = '<h1>自定义标签</h1><div><img src="//jd.com/23.jpg" alt="产品主图" move="12" onmousemove="popover()"><a href="javascript:bind()" onclick="">测试</a></div><script>alert("11");</script>';
        $value     = $purifier->process($dirtyHtml);
        $this->assertEquals('<h1>自定义标签</h1><div><img src="//jd.com/23.jpg" alt="产品主图" /><a>测试</a></div>', $value);

        $purifier = new HtmlPurifier('jt\utils\HtmlPurifier::simple');
        $value    = $purifier->process($dirtyHtml);
        $this->assertEquals('自定义标签<div><img src="//jd.com/23.jpg" alt="23.jpg" />测试</div>', $value);
    }
}
