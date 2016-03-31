<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 19:04
 */

namespace jt;

use jt\exception\TaskException;

/**
 * 给客户端输出请求结果
 *
 * @package jt
 */
class Responder
{
    /**
     * 解析html用到的模板引擎
     *
     * @type null
     */
    protected static $tplEngine = null;

    /**
     * 生成内容
     *
     * @return string
     */
    protected static function render()
    {
        switch (Controller::current()->getMime()) {
            case 'json':
                return self::json();
            case 'html':

                return self::html();
            case 'xml':
                return self::xml();
        }
    }

    /**
     * 以json响应客户端
     */
    protected static function json()
    {
        \header('Content-type: application/json; charset=' . \Config::CHARSET);
        $header         = Error::prepareHeader();
        $header         = array_merge($header, Action::getHeaderStore());
        $header['data'] = Action::getDataStore();

        $content = \json_encode($header, \Config::JSON_FORMAT);

        return $content;
    }

    /**
     * 输出HTML
     */
    protected static function html()
    {
        \header('Content-type: text/html; charset=' . \Config::CHARSET);
        $data = Action::getDataStore();
        if (self::$tplEngine) {
            $tpl = self::$tplEngine;
        }elseif (class_exists('TPLConfig', false)) {
            $tpl = new Template(\TPLConfig::getValues());
        }else {
            return var_export($data, true);
        }
        
        $content = $tpl->render(Controller::current()->getTemplate(), $data);
        if (RUN_MODE !== 'production') {
            //Debug::output($content);
            $hData    = Error::prepareHeader();
            $errorMsg = '';
            foreach (['fatal', 'notice', 'info'] as $type) {
                if (isset($hData[$type])) {
                    $errorMsg .= '<div class="error-type">' . $type . '</div>';
                    $errorMsg .= '<div class="error-desc">' . var_export($hData[$type], true) . '</div>';
                }
            }
            if ($errorMsg) {
                str_replace('</body>', '<div class="error-msg-box">' . $errorMsg . '</div></body>', $content);
            }
        }

        return $content;
    }


    /**
     * 将数组转为XML
     *
     * @param                   $array
     * @param \SimpleXMLElement $xml
     *
     * @return mixed
     */
    private static function array2xml($array, $xml)
    {
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }
            if (is_array($value)) {
                self::array2xml($value, $xml->addChild($key));
            }else {
                $xml->addChild($key, \htmlspecialchars($value));
            }
        }

        return $xml;
    }

    /**
     * 输出xml
     */
    protected static function xml()
    {
        \header('Content-type: application/xml; charset=' . \Config::CHARSET);
        $header         = Error::prepareHeader();
        $header         = array_merge($header, Action::getHeaderStore());
        $header['data'] = Action::getDataStore();
        $content        = self::array2xml($header, new \SimpleXMLElement('<root></root>'))->asXML();

        return $content;
    }

    /**
     * 输出结果
     */
    public static function write()
    {
        $content = static::render();
        //拦截
        echo $content;
    }

    /**
     * 跳转到指定地址
     *
     * @param     $url
     * @param int $status
     */
    public static function redirect($url, $status = 302)
    {
        header('Location:' . $url, true, $status);
        self::end($status);
    }

    /**
     * 结束本次请求
     *
     * @param int $status
     * @throws \jt\exception\TaskException
     */
    public static function end($status = null)
    {
        if ($status) {
            \header('Status: ' . $status, true);
        }

        $e = new TaskException('User end task');
        $e->setType('taskEnd');

        throw $e;
    }

    /**
     * 设置解析HTML用到的模板引擎
     *
     * @param $engine
     */
    public static function setTplEngine($engine)
    {
        static::$tplEngine = $engine;
    }
}