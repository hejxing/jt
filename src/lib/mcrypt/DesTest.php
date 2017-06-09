<?php
/**
 * Auth: ax
 * Created: 2017/5/15 19:42
 */

namespace jt\lib\mcrypt;

use PHPUnit\Framework\TestCase;

class DesTest extends TestCase
{
    public function testEncrypt(){
        $encrypt = new Des('e9wX4(3C');
        $this->assertEquals('123456', $encrypt->decrypt($encrypt->encrypt('123456')));
    }
}
