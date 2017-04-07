<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/4/19 14:58
 */

namespace jt\utils;


use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testRandString()
    {
        echo Helper::randString(2, JT_CHAR_ZN_CH);
    }
}
