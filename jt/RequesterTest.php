<?php

/**
 * Auth: ax@jentian.com
 * Create: 2015/10/14 17:21
 */
class RequesterTest extends PHPUnit_Framework_TestCase
{

    public function testGet()
    {
        $requester = \jt\Requester::create([
            'name'         => 'hejxing',
            'age'          => '30',
            'sex'          => '2',
            'inChina'      => 'T',
            'longHair'     => '0',
            'likeLanguage' => 'php,c,  go, js',
            'novalidate'   => 'we'
        ], [
            'likeLanguage' => 'array',
            'hair'         => 'bool use:sex',
            'novalidate'   => 'min:10'
        ]);

        $this->assertEquals('hejxing', $requester->get('name'));
        $this->assertEquals('hejxing', $requester->get('alias', 'use:name'));
        $this->assertEquals('30', $requester->get('age'));
        $this->assertEquals(30, $requester->get('age', 'int'));
        $this->assertTrue($requester->get('inChina', 'bool'));
        $this->assertTrue($requester->hair);
        $this->assertEquals(null, $requester->notExists);
        $this->assertTrue($requester->has('name'));
        $this->assertFalse($requester->has('novalidate'));
        $this->assertFalse($requester->has('notExists'));
        $this->assertTrue(is_array($requester->get('likeLanguage')));
        $this->assertEquals(['php', 'c', 'go', 'js'], $requester->get('likeLanguage', 'array'));
        $this->assertEquals(6, count($requester->fetchAll()));
        $this->assertEquals(1, count($requester->fetch('name')));
    }

    public function testFetchAll()
    {
        $requester = \jt\Requester::create([
            'name'         => 'hejxing',
            'age'          => '30',
            'inChina'      => 'T',
            'likeLanguage' => 'php,c,  go, js',
            'novalidate'   => 'we',
            'sex'          => 2
        ], [
            'likeLanguage' => 'array',
            'hair'         => 'bool use:sex',
            'novalidate'   => 'min:10',
            'age'          => 'int',
            'in'           => 'bool use:inChina'
        ]);
        $this->assertEquals([
            'name'         => 'hejxing',
            'age'          => 30,
            'in'           => true,
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetchAll());

        $this->assertEquals([
            'age'          => 30,
            'name'         => 'hejxing',
            'in'           => true,
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetch('age,name , in, likeLanguage   ,hair'));
        $this->assertEquals([
            'age'          => 30,
            'name'         => 'hejxing',
            'in'           => true,
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetch('age,   name ,   in   ,likeLanguage,hair'));
        $this->assertEquals([
            'age'          => 30,
            'name'         => 'hejxing',
            'in'           => true,
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetch('age,name', 'in', 'likeLanguage,hair'));
    }

    public function testNeedOne()
    {
        $requester = \jt\Requester::create([
            'name'         => 'hejxing',
            'age'          => '30',
            'inChina'      => 'T',
            'likeLanguage' => 'php,c,  go, js',
            'novalidate'   => 'we',
            'sex'          => 2
        ], [
            'likeLanguage' => 'array',
            'hair'         => 'bool use:sex',
            'novalidate'   => 'min:10',
            'age'          => 'int',
            'in'           => 'bool use:inChina'
        ]);
        $this->assertEquals(true, $requester->needOne(['age', 'in']));
        $this->assertEquals(true, $requester->needOne(['age', 'notExists']));
        $this->assertEquals(false, $requester->needOne(['notExists_0', 'notExists_1']));
    }

    public function testFetchExclude()
    {
        $requester = \jt\Requester::create([
            'name'         => 'hejxing',
            'age'          => '30',
            'inChina'      => 'T',
            'likeLanguage' => 'php,c,  go, js',
            'novalidate'   => 'we',
            'sex'          => 2
        ], [
            'likeLanguage' => 'array',
            'hair'         => 'bool use:sex',
            'novalidate'   => 'min:10',
            'age'          => 'int',
            'in'           => 'bool use:inChina'
        ]);

        $this->assertEquals([
            'name'         => 'hejxing',
            'in'           => true,
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetchExclude('age'));
        $this->assertEquals([
            'name'         => 'hejxing',
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetchExclude('age,in'));
        $this->assertEquals([
            'name'         => 'hejxing',
            'likeLanguage' => [
                'php',
                'c',
                'go',
                'js'
            ],
            'hair'         => true
        ], $requester->fetchExclude('age', 'in'));
    }

    public function testConvert()
    {
        $requester = \jt\Requester::create([]);
        $this->assertEquals(false, $requester->convert('0', 'bool'));
        $this->assertEquals(false, $requester->convert('0.0', 'bool'));
        $this->assertEquals(true, $requester->convert('0.001', 'bool'));
        $this->assertEquals(false, $requester->convert('f', 'bool'));
        $this->assertEquals(false, $requester->convert('n', 'bool'));
        $this->assertEquals(false, $requester->convert('no', 'bool'));
        $this->assertEquals(false, $requester->convert('false', 'bool'));
        $this->assertEquals(false, $requester->convert('no', 'bool'));
    }

    public function testHas()
    {
        $requester = \jt\Requester::create([
            'name'         => 'hejxing',
            'age'          => '30',
            'inChina'      => 'T',
            'likeLanguage' => 'php,c,  go, js',
            'novalidate'   => 'we',
            'sex'          => 2
        ], [
            'likeLanguage' => 'array',
            'hair'         => 'bool use:sex',
            'novalidate'   => 'min:10',
            'age'          => 'int',
            'in'           => 'bool use:inChina'
        ]);

        $this->assertFalse($requester->has('novalidate'));
        $this->assertFalse($requester->has('noExists_1'));
        $this->assertTrue($requester->has('name'));
        $this->assertTrue($requester->has('likeLanguage'));
    }
}