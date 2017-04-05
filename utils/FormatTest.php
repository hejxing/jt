<?php
/**
 * Created by PhpStorm.
 * User: ax
 * Date: 2016/8/13
 * Time: 11:26
 */

namespace jt\utils;


use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public function testSeparateName()
    {
        $res = Format::separateName('潘金莲');
        $this->assertEquals($res, ['潘', '金莲']);

        $res = Format::separateName('西门庆');
        $this->assertEquals($res, ['西门', '庆']);

        $res = Format::separateName('西门吹雪');
        $this->assertEquals($res, ['西门', '吹雪']);

        $res = Format::separateName('西');
        $this->assertEquals($res, ['', '西']);

        $res = Format::separateName('张生');
        $this->assertEquals($res, ['张', '生']);

        $res = Format::separateName('西门吹雪');
        $this->assertEquals($res, ['西门', '吹雪']);

        $res = Format::separateName('Lily');
        $this->assertEquals($res, ['', 'Lily']);
    }
}
