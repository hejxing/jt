<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/19 14:58
 */

namespace jt\utils;


class HelperTest extends \PHPUnit_Framework_TestCase
{
    public function testRandString(){
        echo Helper::randString(2, JT_CHAR_ZN_CH);
    }
}
