<?php
/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2015/6/4 15:35
 */

namespace jt\utils;

use jt\Responder;

/**
 * 与处理URL相关的工具集
 *
 * @package jt\utils
 */
class Url
{
    /**
     * 绝对定位到当前主机
     *
     * @return string
     */
    public static function host()
    {
        $pageURL = 'http';

        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://" . $_SERVER['HTTP_HOST'];

        if ($_SERVER['SERVER_PORT'] != '80') {
            $pageURL .= ':' . $_SERVER['SERVER_PORT'];
        }

        return $pageURL;
    }

    /**
     * 生成当前页面地址
     *
     * @return string
     */
    public static function current()
    {
        $pageURL = self::host();

        $pageURL .= $_SERVER['REQUEST_URI'];

        return $pageURL;
    }

    /**
     * 生成当前页面路径
     */
    public static function currentURI()
    {
        return $_SERVER['REQUEST_URI'];
    }

    /**
     * 生成链接
     *
     * @param string $name
     * @param array  $param
     * @param string $suffix 生成的网址的后缀
     *
     * @return string
     */
    public static function make($name, array $param = [], $suffix = '')
    {
        if (strpos($name, '/') !== false) {//直接地址，不用再生成
            return $name;
        }
        $url = '/';
        $url .= self::general($name, $param);
        if ($suffix) {
            $url .= '.' . $suffix;
        }

        return $url;
    }

    /**
     * 根据路由规则生成网址
     *
     * @param string $action 要链接到的Action
     * @param array  $param 参数
     *
     * @return string
     */
    public static function general($action, array $param = [])
    {
        $uris = [];
        if (!array_key_exists($action, \Config::$routerMap)) {
            return 'routerNotExists';
        }
        $patterns = \explode('/', \Config::$routerMap[$action][0]);
        foreach ($patterns as $p) {
            preg_match('/^:(\w*):?(\w*)$/', $p, $match);
            if (count($match)) {
                if (!array_key_exists($match[1], $param)) {
                    return 'paramNotExists';
                }
                $uris[] = $param[$match[1]];
            }else {
                $uris[] = $p;
            }
        }

        return implode('/', $uris);
    }

    /**
     * 获取图片的url
     *
     * @param string $path 数据库里存储的图片路径属性值
     * @param string $spec 图片规格
     * @param string $size 图片尺寸
     * @param bool   $original 是否输出原始图
     *
     * @return string
     */
    public static function img($path, $spec = '', $size = '170', $original = false)
    {
        if (!empty($path)) {
            if (preg_match('/^\/\//', $path) || preg_match('/^http[s]?:/', $path)) {
                return $path;
            }
            if ($original) {
                return \Config::OriginalImgHost . $path;
            }else {
                $pathInfo = pathinfo($path);

                return \Config::ImgHost . $pathInfo['dirname'] . '/' . $spec . '/' . $size . '/' . $pathInfo['basename'];
            }
        }
    }

    /**
     * 跳转到指定页面
     *
     * @param string $name 要跳转到的目标页面
     * @param array  $param 参数
     * @param int    $status HTTP响应代码
     */
    public static function jump($name, array $param = [], $status = 302)
    {
        Responder::redirect(self::make($name, $param), $status);
    }

    /**
     * 在地址后附加参数
     *
     * @param        $data
     * @param string $url
     *
     * @return string
     */
    public static function addQueryParam($data, $url = '')
    {
        if (!$url) {
            $url = self::currentURI();
        }
        if (strpos($url, '?') === false) {
            $queryString = '';
        }else {
            list($url, $queryString) = explode('?', $url, 2);
        }
        parse_str($queryString, $origin);
        $data        = array_merge($origin, $data);
        $queryString = http_build_query($data);

        return $url . '?' . $queryString;
    }

    /**
     * 将url转换成普通字符串，便于传递
     *
     * @param $url
     * @return string
     */
    public static function pack($url)
    {
        return base64_encode($url);
    }

    /**
     * 将普通字符串转回url
     *
     * @param $packed
     * @return string
     */
    public static function unpack($packed)
    {
        return base64_decode($packed);
    }

    /**
     * 解析QueryString
     * @param $url
     * @return array
     */
    public static function parseQueryString($url)
    {
        $param = [];
        if (strpos($url, '?')) {
            list(, $queryString) = explode('?', $url, 2);
            parse_str($queryString, $param);
        }

        return $param;
    }

    /**
     * 重载当前页
     */
    public static function reload()
    {
        Responder::redirect(self::currentURI());
    }
}