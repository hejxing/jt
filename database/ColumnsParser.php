<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/25
 * Time: 2:09
 */

namespace jt\database;


class ColumnsParser
{
    /**
     * 值为true or false的键
     *
     * @type array
     */
    protected static $boolList = ['require', 'increment', 'primary', 'hidden', 'visible', 'fillable', 'guarded'];
    /**
     * 字段类型
     *
     * @type array
     */
    protected static $types = [
        'uuid',
        'bit',
        'bytea',
        'inet',
        'int2',
        'int4',
        'int8',
        'json',
        'bool',
        'box',
        'date',
        'time',
        'timestamp',
        'float4',
        'float8'
    ];
    /**
     * 需要长度的类型
     *
     * @type array
     */
    protected static $typesWithLength = ['char', 'varchar', 'text'];
    /**
     * 需要执行特殊处理的值
     *
     * @type array
     */
    protected static $command = ['format', 'touch', 'foreign', 'as'];

    /**
     * 模型中字段解析器
     *
     * @param array $columns
     * @return array
     */
    public static function parse($columns)
    {
        $parsed = [];
        foreach ($columns as $name => $attr) {
            $parsed[$name] = self::line($attr);
        }

        return $parsed;
    }

    public static function line($str)
    {
        $lined = [];
        $parts = \explode(' ', $str);
        foreach ($parts as $a) {
            $lined = array_merge($lined, self::attr($a));
        }

        return $lined;
    }

    public static function attr($a)
    {
        if (strpos($a, ':')) {
            list($key, $value) = explode(':', $a, 2);
        }else {
            list($key, $value) = [$a, null];
        }
        $result = [];
        switch (true) {
            case in_array($key, self::$boolList):
                $result[$key] = $value === null || ($value !== 'false' && (bool)$value);
                break;
            case in_array($key, self::$typesWithLength):
                $result['length'] = intval($value);
            case in_array($key, self::$types):
                $result['type'] = $key;
                break;
            case in_array($key, self::$command):
                $result[$key] = $value;
                break;
        }

        return $result;
    }
}