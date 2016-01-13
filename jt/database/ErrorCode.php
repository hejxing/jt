<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2016/1/13 18:54
 */

namespace jt\database;


class ErrorCode
{
    private static $pgsql = [
        '23505' => 'duplicate'
    ];
    public static function getName($type, $code){
        switch($type){
            case 'pgsql':
                if(isset(self::$pgsql[$code])){
                    return self::$pgsql[$code];
                }
                break;
            case 'mysql':
                break;
        }
        return null;
    }
}