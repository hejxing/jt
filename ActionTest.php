<?php
/**
 * Auth: ax@csmall.com
 * Create: 2015/10/20 10:48
 */

namespace jt;


use PHPUnit\Framework\TestCase;

class ActionTest extends TestCase
{
    private function outTotal($total, $size, $page)
    {
        echo $page;

        return [$total, $size, $page];
    }

    public function testOutTotal()
    {
        $pageInfo = [
            10,
            5,
            2
        ];
        $this->assertEquals([10, 5, 2], $this->outTotal(...$pageInfo));
    }
}
