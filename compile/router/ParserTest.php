<?php
/**
 * @Auth ax@jentian.com
 * @Create 2015/10/23 13:25
 */

namespace jt\compile\router;


class ParserTest extends \PHPUnit_Framework_TestCase
{
    public function testParser()
    {
        $routerMapFile = \Config::RUNTIME_PATH_ROOT . '/router/' . MODULE . '.php';
        $result        = Router::general($routerMapFile);
        var_export($result);
    }
}
