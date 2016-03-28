<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2015/11/9
 * Time: 18:11
 */

namespace jt;


class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function testEqualsMulti()
    {
        $model = new \sys\model\User();
        $model->equalsMulti(['name' => 'apple']);
        $this->assertEquals(1, 1);
    }

    public function testArrayPointAfter()
    {
        $array = [1, 2, 3];
        while (list($key, $item) = each($array)) {
            echo "$key => $item,\r\n";
        }
        var_export(prev($array));//false
        $this->assertFalse(prev($array));
        //fix
        $res = prev($array);
        if ($res === false) {
            end($array);
        }
    }

    public function testArrayPointBefore()
    {
        $array = [1, 2, 3];
        prev($array);
        var_export(next($array));//false
        $this->assertFalse(next($array));
        //fix
        $res = next($array);
        if ($res === false) {
            reset($array);
        }
    }
}
