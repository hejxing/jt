<?php
/**
 * Auth: ax
 * Date: 15-4-3 22:13
 */

namespace jt\utils;

use jt\lib\markdown\michelf\Markdown;

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
     * 移除UUID的分隔符
     * @param string $uuid 带分隔符-的UUID，36位
     * @return string 移除分隔符后的UUID，32位
     */
    public static function uuidSeparatorRemove($uuid){
        return str_replace('-', '', $uuid);
    }

    /**
     * 给UUID加上分隔符
     * @param string $uuid 不带分隔符-的UUID，32位
     * @return string 加上分隔符后的UUID，36位
     */
    public static function uuidSeparatorAdd($uuid){
        return self::insertStr($uuid, [8, 12, 16, 20], '-');
    }

    /**
     * 在指定位置插入指定字符串
     * @param string $str 原字符串
     * @param int|array $offset 位置偏移量，单个或数组
     * @param string $input 插入的字符串
     * @return string 返回新的字符串
     */
    private static function insertStr($str, $offset, $input)
    {
        $newStr = '';
        for ($i = 0; $i < strlen($str); $i++){
            if (is_array($offset)){//如果插入是多个位置
                foreach ($offset as $v){
                    if ($i == $v){
                        $newStr .= $input;
                    }
                }
            }else{//直接是一个位置偏移量
                if ($i == $offset){
                    $newStr .= $input;
                }
            }
            $newStr .= $str[$i];
        }
        return $newStr;
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
     * 判断当前打开的浏览器是否是微信浏览器
     * @return boolean true/false
     */
    public static function isWeixinBrowser(){
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    /**
     * 简单属性映射，带索引的内容将进行映射
     *
     * @param array $map
     * @param array $data
     * @return array
     */
    public static function simpleMap($map, $data)
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
     * 搜集属性列表，带索引的内容将进行映射，支持复杂的转换规则
     *
     * @param array $map
     * @param array $data
     * @param bool $ignoreEmpty
     * @return array
     */
    public static function map($map, $data, $ignoreEmpty = false)
    {
        $result     = [];
        $indexAssoc = false;
        foreach($map as $n => $v){
            if(is_int($n)){
                $n          = $v;
                $indexAssoc = true;
            }

            if(is_callable($v)){
                $result[$n] = call_user_func_array($v, [$data]);
                continue;
            }

            $type = 'string';
            if(strpos($n, '(') === 0){
                preg_match('/^\((.*)\)(.+)/', $n, $matched);
                if(count($matched) > 2){
                    $type = $matched[1];
                    $n    = $matched[2];
                    if($indexAssoc){
                        $v = $n;
                    }
                }
            }

            $vns   = explode('.', $v);
            $value = $data;
            foreach($vns as $vn){
                if(isset($value[$vn])){
                    $value = $value[$vn];
                }elseif(substr($vn, 0, 1) === '"' && substr($vn, -1, 1) === '"'){
                    $value = substr($vn, 1, -1);
                }else{
                    $value = null;
                    break;
                }
            }
            if($value === null && $ignoreEmpty){
                continue;
            }
            switch($type){
                case 'int':
                    $value = intval($value?: 0);
                    break;
                case 'float':
                    $value = floatval($value?: 0);
                    break;
                case 'bool':
                    $value = boolval($value);
                    break;
                default:
                    $value = $value === null? '': $value;
                    break;
            }
            $result[$n] = $value;
        }

        return $result;
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
     * 获取IP地址所在的城市
     * @param string $ip IP地址
     * @return string 城市
     */
    public static function getIpCity($ip){
        $city = '';
        if($ip){
            $res = \jt\utils\Transfer::getContent('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=js&ip='. $ip);
            if(!empty($res)){
                $jsonMatches = [];
                preg_match('#\{.+?\}#', $res, $jsonMatches);
                if(isset($jsonMatches[0])){
                    $json = json_decode($jsonMatches[0], true);
                    if(isset($json['ret']) && $json['ret'] == 1){
                        $city = $json['country'] . $json['province'] . $json['city'] . $json['isp'];
                    }
                }
            }
        }
        return $city;
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

    /**
     * 将md文档转为html
     * @param string $file
     * @return string
     */
    public static function readMD($file)
    {
        $markerDown = new Markdown();

        return $markerDown->defaultTransform(file_get_contents($file));
    }

    /**
     * 获取当前网站所用的域名
     * @return string
     */
    public static function getDomain(){
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * 绝对定位到当前主机
     * @return string
     */
    public static function getHost(){
        return 'http'.((isset($_SERVER['SERVER_PORT_SECURE']) && (int)$_SERVER['SERVER_PORT_SECURE'])?'s':'').'://'.$_SERVER['HTTP_HOST'];
    }

    /**
     * 获取当前访问页面的完整URL
     * @return string
     */
    public static function getUrl(){
        return 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'];
    }

    /**
     * 获取浏览器版本
     * @return string
     */
    public static function getBrowserVersion(){
        $httpUserAgent = $_SERVER["HTTP_USER_AGENT"];
        if(strpos($httpUserAgent, 'MSIE 8.0')){
            return 'IE8';
        }if(strpos($httpUserAgent, 'MSIE 9.0')){
            return 'IE9';
        }else if(strpos($httpUserAgent, 'MSIE 7.0')){
            return 'IE7';
        }else if(strpos($httpUserAgent, 'MSIE 6.0')){
            return 'IE6';
        }else if(strpos($httpUserAgent, 'Firefox/3')){
            return 'FIREFOX3';
        }else if(strpos($httpUserAgent, 'Firefox/2')){
            return 'FIREFOX2';
        }else if(strpos($httpUserAgent, 'Chrome')){
            return 'CHROME';
        }else if(strpos($httpUserAgent, 'Safari')){
            return 'SAFARI';
        }else if(strpos($httpUserAgent, 'Opera')){
            return 'OPERA';
        }else{
            return $httpUserAgent;
        }
    }

    /**
     * 获取浏览器名称
     * @return string
     */
    public static function getBrowser()
    {
        $userAgent = strtolower($_SERVER["HTTP_USER_AGENT"]);
        $browser   = "其他";

        //判断是否是myie
        if(strpos($userAgent, "myie")){
            $browser = "蚂蚁浏览器";
        }

        //判断是否是Netscape
        if(strpos($userAgent, "netscape")){
            $browser = "网景浏览器";
        }

        //判断是否是Opera
        if(strpos($userAgent, "opera")){
            $browser = "欧朋浏览器";
        }

        //判断是否是netcaptor
        if(strpos($userAgent, "netcaptor")){
            $browser = "netCaptor";
        }

        //判断是否是TencentTraveler
        if(strpos($userAgent, "tencenttraveler")){
            $browser = "腾讯TT浏览器";
        }

        //判断是否是微信浏览器
        if(strpos($userAgent, "micromessenger")){
            $browser = "微信浏览器";
        }

        //判断是否是QQ浏览器
        if(strpos($userAgent, "mqqbrowser")){
            $browser = "QQ浏览器";
        }

        //判断是否是UC浏览器
        if(strpos($userAgent, "ucbrowser") || strpos($userAgent, "ucweb")){
            $browser = "UC浏览器";
        }

        //判断是否是Firefox
        if(strpos($userAgent, "firefox")){
            $browser = "火狐浏览器";
        }

        //判断是否是ie
        if(strpos($userAgent, "msie") || strpos($userAgent, "trident")){
            $browser = "IE浏览器";
        }

        //判断是否是chrome内核浏览器
        if(strpos($userAgent, "chrome")){
            $browser = "谷歌浏览器";
        }

        return $browser;
    }

    /**
     * 获取用户USER_AGENT信息，判断终端平台系统
     * @return string
     */
    public static function getPlatform(){
        //$browserPlatform = '';
        $Agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strstr($Agent, 'win') && strstr($Agent, 'nt 5.1')){
            $browserPlatform = "Windows XP";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 6.1')){
            $browserPlatform = "Windows 7";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 6.2')){
            $browserPlatform = "Windows 8";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 6.3')){
            $browserPlatform = "Windows 8";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 6.4')){
            $browserPlatform = "Windows 8";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 10.0')){
            $browserPlatform = "Windows 10";
        }elseif(strstr($Agent, 'android')){
            $browserPlatform = "Android";
        }elseif(strstr($Agent, 'iphone')){
            $browserPlatform = "iPhone";
        }elseif(strstr($Agent, 'mac os')){
            $browserPlatform = "Mac OS";
        }elseif(strstr($Agent, 'ipad')){
            $browserPlatform = "iPad";
        }elseif(strstr($Agent, 'ipod')){
            $browserPlatform = "iPod";
        }elseif(strstr($Agent, 'linux')){
            $browserPlatform = "Linux";
        }elseif(strstr($Agent, 'unix')){
            $browserPlatform = "Unix";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 6.0')){
            $browserPlatform = "Windows Vista";
        }elseif(strstr($Agent, 'win') && strstr($Agent, '32')){
            $browserPlatform = "Windows 32";
        }elseif(strstr($Agent, 'win') && strstr($Agent, '95')){
            $browserPlatform = "Windows 95";
        }elseif(strstr($Agent, 'win') && strstr($Agent, '98')){
            $browserPlatform = "Windows 98";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt 5.0')){
            $browserPlatform = "Windows 2000";
        }elseif(strstr($Agent, 'win') && strstr($Agent, 'nt')){
            $browserPlatform = "Windows NT";
        }elseif(strstr($Agent, 'win 9x') && strstr($Agent, '4.90')){
            $browserPlatform = "Windows ME";
        }elseif(strstr($Agent, 'sun') && strstr($Agent, 'os')){
            $browserPlatform = "SunOS";
        }elseif(strstr($Agent, 'ibm') && strstr($Agent, 'os')){
            $browserPlatform = "IBM OS/2";
        }elseif(strstr($Agent, 'mac') && strstr($Agent, 'pc')){
            $browserPlatform = "Macintosh";
        }elseif(strstr($Agent, 'powerpc')){
            $browserPlatform = "PowerPC";
        }elseif(strstr($Agent, 'aix')){
            $browserPlatform = "AIX";
        }elseif(strstr($Agent, 'hpux')){
            $browserPlatform = "HPUX";
        }elseif(strstr($Agent, 'netbsd')){
            $browserPlatform = "NetBSD";
        }elseif(strstr($Agent, 'bsd')){
            $browserPlatform = "BSD";
        }elseif(strstr($Agent, 'osf1')){
            $browserPlatform = "OSF1";
        }elseif(strstr($Agent, 'irix')){
            $browserPlatform = "IRIX";
        }elseif(strstr($Agent, 'freebsd')){
            $browserPlatform = "FreeBSD";
        }else{
            $browserPlatform = "Other";
        }

        return $browserPlatform;
    }

    /**
     * 根据时间戳获取指定某天起止时间戳
     * @param string $time 当天任意时间戳
     * @param int $range 前后天数,如后一天为1，前一天为-1
     * @return array [开始时间,结束时间]
     */
    public static function getDayRangeTime($time = null, $range = 0){
        $time = $time ? $time : RUN_START_TIME;
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('d', $time) + $range;
        return [mktime(0, 0, 0, $m, $d, $y), mktime(23, 59, 59, $m, $d, $y)];
    }

    /**
     * 根据时间戳获取周起止时间戳
     * @param string $time 当天任意时间戳
     * @param int $range 前后周数,如下周+1，上周-1
     * @return array [开始时间,结束时间]
     */
    public static function getWeekRangeTime($time = null, $range = 0){
        $time = $time ? $time : RUN_START_TIME;
        $y = date('Y', $time);
        $m = date('m', $time);
        $d = date('d', $time);
        $w = date('w', $time) + $range;
        return [mktime(0, 0, 0, $m, $d-$w+1, $y), mktime(23, 59, 59, $m, $d-$w+7, $y)];
    }

    /**
     * 根据时间戳获取指定月份起止时间戳
     * @param string $time 当月任意时间戳
     * @param int $range 前后月份数,如后一月为1，前一月为-1
     * @return array [开始时间,结束时间]
     */
    public static function getMonthRangeTime($time = null, $range = 0){
        $time = $time ? $time : RUN_START_TIME;
        $y = date('Y', $time);
        $m = date('m', $time) + $range;
        return [mktime(0, 0, 0, $m, 1, $y), mktime(0, 0, 0, $m+1, 1, $y)-1];
    }
}