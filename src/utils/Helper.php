<?php
/**Auth: ax
 * Date: 15-4-3 22:13
 */

namespace jt\utils;

define('JT_CHAR_NUMBER', 1);
define('JT_CHAR_LOWERCASE', 2);
define('JT_CHAR_UPPERCASE', 4);
define('JT_CHAR_ZN_CH', 8);

class Helper
{
    /**
     * 生成UUID
     * 允许传入
     *
     * @param array  $default 默认的位置值
     * @param string $split 分隔符
     * @return string
     */
    public static function uuid(array $default = [], $split = '')
    {
        $partLength = [8, 4, 4, 4, 12];
        foreach($partLength as $i => $length){
            if(isset($default[$i])){
                $default[$i] = str_pad(substr($default[$i], 0, $length), $length, '0', STR_PAD_LEFT);
            }else{
                $default[$i] = '';
                while(strlen($default[$i]) < $length){
                    $default[$i] .= str_pad(base_convert(mt_rand(0, 65535), 10, 16), 4, '0', STR_PAD_LEFT);
                }
            }
        }
        ksort($default);

        return implode($split, $default);
    }

    /**
     * 生成密码
     *
     * @param string $pwd 密码明文
     * @param string $salt 扰乱因子
     * @return string
     */
    public static function encrypt($pwd, $salt)
    {
        return \md5($salt.$pwd.$salt);
    }

    /**
     * 删除索引数组的键，转换为普通数组
     *
     * @param array $array 要转换的索引数组
     * @return array 转换后的普通数组
     */
    public static function delArrayAssoc($array)
    {
        $newArr = [];
        foreach($array as $value){
            $newArr[] = $value;
        }

        return $newArr;
    }

    /**
     * 解析JSON为数组,如果字符串为空，返回空数组
     *
     * @param $json
     * @return mixed
     */
    public static function decodeJSON($json)
    {
        if(is_string($json) && !empty($json)){
            return json_decode($json, true);
        }else{
            return [];
        }
    }

    /**
     * 将数据序列化成json,将不对中文进行编码
     *
     * @param $data
     * @return string
     */
    public static function encodeJSON($data)
    {
        if(is_string($data)){
            return $data;
        }else{
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 平台类型
     *
     * @return string
     */
    public static function deviceType()
    {
        //获取USER AGENT
        $map   = [
            'pc'      => 'windows nt',
            'iphone'  => 'iphone',
            'ipad'    => 'ipad',
            'android' => 'android'
        ];
        $agent = \strtolower($_SERVER['HTTP_USER_AGENT']);
        foreach($map as $type => $flag){
            if(\strpos($agent, $flag) !== false){
                return $type;
            }
        }

        return 'unknown';
    }

    /**
     * 判断是否为该终端
     *
     * @param string $flag MicroMessenger,
     *
     * @return bool
     */
    public static function isTheAgent($flag)
    {
        return (\strpos($_SERVER['HTTP_USER_AGENT'], $flag) !== false);
    }

    /**
     * 搜集属性列表，带索的内容将进行映射
     *
     * @param $map
     * @param $data
     * @return array
     */
    public static function mapList($map, $data)
    {
        $list = [];
        foreach($map as $key => $value){
            if(is_int($key)){
                $key = $value;
            }
            if(isset($data[$key])){
                $list[$value] = $data[$key];
            }
        }

        return $list;
    }

    /**
     * 获取客户端IP,检查代理(代理通常可伪造)
     *
     * @return string
     */
    private static function getClientIp()
    {
        $ip = null;
        if(isset($_SERVER)){
            if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
                foreach($arr as $ip){
                    $ip = trim($ip);
                    if($ip != 'unknown'){
                        break;
                    }
                }
            }else{
                if(isset($_SERVER['HTTP_CLIENT_IP'])){
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                }
            }
        }else{
            if(getenv('HTTP_X_FORWARDED_FOR')){
                $ip = getenv('HTTP_X_FORWARDED_FOR');
            }elseif(getenv('HTTP_CLIENT_IP')){
                $ip = getenv('HTTP_CLIENT_IP');
            }
        }
        if($ip === null){
            $ip = self::getShakeIp();
        }

        return $ip;
    }

    private static function getShakeIp()
    {
        return $_SERVER['REMOTE_ADDR']??getenv('REMOTE_ADDR')?: '0.0.0.0';
    }

    /**
     * 获取访客的IP地址
     *
     * @param bool $proxy 是否透过代理获取"真实IP"? 警告:该IP可以伪造
     * @param bool $long 返回的类型;true:将IP地址转换成整型返回;false:直接返回IP串
     * @return string||long
     */
    public static function getIp($proxy = true, $long = false)
    {
        if($proxy){
            /* 这类IP皆是可伪造的HTTP报文 */
            //此处为http报文,可伪造,不可靠
            $ip = self::getClientIp();
        }else{
            $ip = self::getShakeIp();
        }

        return $long? ip2long($ip): $ip;
    }

    /**
     * 将unicode数字编码转为字符
     *
     * @param $dec
     * @return string
     */
    public static function uniChr($dec)
    {
        if($dec < 128){
            $utf = chr($dec);
        }else{
            if($dec < 2048){
                $utf = chr(192 + (($dec - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }else{
                $utf = chr(224 + (($dec - ($dec % 4096)) / 4096));
                $utf .= chr(128 + ((($dec % 4096) - ($dec % 64)) / 64));
                $utf .= chr(128 + ($dec % 64));
            }
        }

        return $utf;
    }

    /**
     * 产生随机字符
     *
     * @param     $length
     * @param int $mask
     * @return string
     */
    public static function randString($length, $mask = JT_CHAR_NUMBER)
    {
        $randomString = "";
        $type         = [];
        foreach([1, 2, 4, 8] as $t){
            if($t & $mask){
                $type[] = $t;
            }
        }
        while($length){
            $c = '';
            switch($type[array_rand($type)]){
                case JT_CHAR_NUMBER:
                    $c = chr(mt_rand(48, 57));
                    break;
                case JT_CHAR_LOWERCASE:
                    $c = chr(mt_rand(97, 122));
                    break;
                case JT_CHAR_UPPERCASE:
                    $c = chr(mt_rand(65, 90));
                    break;
                case JT_CHAR_ZN_CH:
                    $c = self::uniChr(mt_rand(0x4e00, 0x9fa5));
                    break;
            }
            $randomString .= $c;
            $length--;
        }

        return $randomString;
    }

    /**
     * 修改文件或文件夹权限
     *
     * @param $path
     * @param $perms
     */
    public static function modifyFilePerms($path, $perms)
    {
        if(is_dir($path)){
            $dir      = new \RecursiveDirectoryIterator($path);
            $iterator = new \RecursiveIteratorIterator($dir, \RecursiveIteratorIterator::SELF_FIRST);
            foreach($iterator as $item){
                $fileName = (string)$item;
                if(substr($fileName, -1) !== '.' && !(fileperms($fileName) & 0x0002)){
                    chmod($fileName, $perms);
                }
            }
        }else{
            chmod($path, $perms);
        }
    }

    /**
     * 获取文件中最后一行的内容
     *
     * @param string $file
     * @return string
     */
    public static function readLastLine($file)
    {
        $fp = fopen($file, 'r');
        if($fp === false){
            return '';
        }
        fseek($fp, -1, SEEK_END);
        $line = '';
        while(($c = fgetc($fp)) !== false){
            if($c === "\n" || $c === "\r"){
                if($line){
                    break;
                }
            }else{
                $line = $c.$line;
            }
            fseek($fp, -2, SEEK_CUR);
        }
        fclose($fp);

        return $line;
    }
}