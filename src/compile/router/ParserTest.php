<?php
/**
 * @Auth ax@csmall.com
 * @Create 2015/10/23 13:25
 */

namespace jt\compile\router;


use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    public function testParser()
    {
        $routerMapFile = RUNTIME_PATH_ROOT.'/router/'.MODULE.'.php';
        $result        = Router::general($routerMapFile, PROJECT_ROOT, MODULE);
        var_export($result);
    }
}
