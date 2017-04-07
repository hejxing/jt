<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2016/8/11
 * Time: 9:46
 */

namespace jt\compile\config;


class Database extends Config
{
    protected static function genCon($result)
    {
        $con = self::genFileHeader();
        $con .= 'return '.var_export($result, true).';';

        return $con;
    }

    protected static function genCacheFileName()
    {
        self::$cacheFile = RUNTIME_PATH_ROOT.'/cache/config/db_'.MODULE.'.php';
    }
}