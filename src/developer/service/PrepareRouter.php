<?php
/**
 * Auth: ax
 * Created: 2017/5/5 2:53
 */

namespace jt\developer\service;


use jt\utils\Helper;

class PrepareRouter
{
    /**
     * @var array
     */
    static private $parsed    = null;
    static private $list      = null;
    static private $classInfo = null;

    /**
     * 读取所有分类信息
     */
    public static function getClassList()
    {
        if (self::$list) {
            return [self::$list, self::$classInfo];
        }
        self::loadParsed();
        $list      = [];
        $classInfo = [];
        foreach (self::$parsed['action'] as $className => $class) {
            $className             = explode('action\\', $className, 2)[1];
            $classInfo[$className] = Helper::simpleMap(['title', 'Auth', 'Create', 'version', 'notice', 'desc'], $class);
            foreach ($class['methods'] as $method) {
                if (strpos($method['affix'], 'doc_hidden') !== false) {
                    continue;
                }
                $path = $method['uri'];
                foreach ($method['methods'] as $m) {
                    $list[$className][$path][$m] = [
                        'name' => $method['name']
                    ];
                }
                if (!empty($list[$className][$path])) {
                    ksort($list[$className][$path]);
                }
            }
            if (!empty($list[$className])) {
                ksort($list[$className]);
            }
        }
        ksort($list);
        self::$list      = $list;
        self::$classInfo = $classInfo;

        return [$list, $classInfo];
    }

    public static function getParsed(){
        self::loadParsed();
        return self::$parsed;
    }

    private static function loadParsed()
    {
        if (self::$parsed === null) {
            $parseFile = RUNTIME_PATH_ROOT.'/cache/router/'.MODULE.'.php';
            self::$parsed = require($parseFile);
        }
    }
}