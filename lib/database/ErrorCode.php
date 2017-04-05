<?php
/**
 * @Copyright csmall.com
 * Auth: hejxi
 * Create: 2016/1/13 18:54
 */

namespace jt\lib\database;


class ErrorCode
{
    private static $pgsql = [
        '23505' => 'duplicate'
    ];

    private static $defaultErrorMsg = [//'duplicate' => '不允许添加相同内容'
    ];

    public static function getDefaultMsg($code)
    {
        if(!empty(self::$defaultErrorMsg[$code])){
            return self::$defaultErrorMsg[$code];
        }else{
            return null;
        }
    }

    /**
     * 获得错误类型
     *
     * @param $type
     * @param $code
     * @return mixed|null
     */
    public static function getName($type, $code)
    {
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

    /**
     * 生成错误消息
     *
     * @param            $type
     * @param \Exception $e
     * @param            $msgList
     * @param string     $sql
     * @return string
     */
    public static function getMessage(\jt\Model $model, \PDOException $e, $sql = '')
    {
        $type    = self::getName($model->getConnectorType(), $e->getCode());
        $msgList = $model->getErrorMsgList();

        $msg = $msgList[$type]??self::getDefaultMsg($type)?: $e->getMessage().' SQL['.$sql.']';
        if($type === 'duplicate' && strpos($msg, '[field]') !== false){
            preg_match('/Key \((.*?)\)/', $e->getMessage(), $matched);
            $msg = str_replace('[field]', $matched[1], $msg);
        }

        return $msg;
    }
}