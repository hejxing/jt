<?php
/**
 * Auth: ax
 * Date: 15-4-26 19:04
 */

namespace jt;

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
     * @type TemplateInterface
     */
    protected $tplEngine = null;

    /**
     * 生成内容
     *
     * @return string
     */
    protected function render()
    {
        switch(Controller::current()->getMime()){
            case 'json':
                return self::json();
            case 'html':

                return self::html();
            case 'xml':
                return self::xml();
        }

        return '';
    }

    /**
     * 以json响应客户端
     */
    protected function json()
    {
        header('Content-type: application/json; charset='.\Config::CHARSET, true);
        $action = Controller::current()->getAction();
        //$start = microtime(true);
        $data = $action->getDataStore();
        //sleep(1);
        //$spend = (microtime(true) - $start) * 1000;
        $header = Error::prepareHeader();
        //$header['parseDataSpendTime'] = $spend;
        $header         = array_replace_recursive($header, $action->getHeaderStore());
        $header['data'] = $data;

        $content = json_encode($header, \Config::JSON_FORMAT);

        return $content;
    }

    protected function makeEngine()
    {
        if(!$this->tplEngine && defined('\Config::TEMPLATE')){
            $engine          = \Config::TEMPLATE['engine']??'\jt\utils\PHPTemplate';
            $this->tplEngine = new $engine(\Config::TEMPLATE);
        }
    }

    /**
     * 输出HTML
     */
    protected function html()
    {
        if(ob_get_level()){
            ob_end_flush();
        }
        header('Content-type: text/html; charset='.\Config::CHARSET, true);
        $data = Controller::current()->getAction()->getDataStore(false);
        $this->makeEngine();
        if($this->tplEngine === null){
            return var_export($data, true);
        }

        if(constant('\Config::WEB_COMMON_DATA')){
            $data = array_merge_recursive(\Config::WEB_COMMON_DATA, $data);
        }

        $content = $this->tplEngine->render(Controller::current()->getTemplate(), $data);
        if(RUN_MODE !== 'production'){
            //Debug::output($content);
            $hData = Error::prepareHeader();
            //$errorMsg = '';
            //foreach (['fatal', 'notice', 'info'] as $type) {
            //    if (isset($hData[$type])) {
            //        $errorMsg .= '<div class="error-type">' . $type . ':</div>';
            //        $errorMsg .= '<pre class="error-desc">' . var_export($hData[$type], true) . '</pre>';
            //    }
            //}
            //if ($errorMsg) {
            //    $content = str_replace('</body>', '<div class="error-msg-box">' . $errorMsg . '</div></body>', $content);
            //}

            $ruler   = Controller::current()->getRuler();
            $sqlList = "\n        ".implode("\n        ", $hData['querySqlList']);
            $content .= "
<!--
    Entrance: {$ruler[0]}::{$ruler[1]} (@router at line: {$ruler[8]})
    SqlQueryTimes: {$hData['queryCount']}
    LoadFiles: {$hData['loadFilesCount']}
    UseMemory: {$hData['useMemory']}
    SpendTime: {$hData['spendTime']}
    
    SqlList: {$sqlList}
-->";
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
    private function array2xml($array, $xml)
    {
        foreach($array as $key => $value){
            if(is_numeric($key)){
                $key = 'item';
            }
            if(is_array($value)){
                self::array2xml($value, $xml->addChild($key));
            }else{
                $xml->addChild($key, \htmlspecialchars($value));
            }
        }

        return $xml;
    }

    /**
     * 输出xml
     */
    protected function xml()
    {
        $action = Controller::current()->getAction();
        header('Content-type: application/xml; charset='.\Config::CHARSET);
        $header         = Error::prepareHeader();
        $header         = array_replace_recursive($header, $action->getHeaderStore());
        $header['data'] = $action->getDataStore();
        $content        = self::array2xml($header, new \SimpleXMLElement('<root></root>'))->asXML();

        return $content;
    }

    /**
     * 输出结果
     */
    public function write()
    {
        if(RUN_MODE === 'production' && ob_get_level()){
            ob_clean();
        }
        $content = $this->render();
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
        header('Location:'.$url, true, $status);
        self::end($status);
    }

    /**
     * 结束本次请求
     *
     * @param int $status
     * @throws Exception
     */
    public static function end($status = null)
    {
        if($status){
            \header('Status: '.$status, true);
        }
        if($status === null || ($status >= 200 && $status < 400)){
            Controller::current()->getAction()->setIsRunComplete(true);
        }

        $e = new Exception('User end task');
        $e->setType('taskEnd');

        throw $e;
    }

    /**
     * 设置解析HTML用到的模板引擎
     *
     * @param $engine
     */
    public function setTplEngine(TemplateInterface $engine)
    {
        $this->tplEngine = $engine;
    }

    /**
     * 是否有缓存
     *
     * @return int 1:有缓存 0:无缓存 -1:不允许缓存
     * @throws Exception
     */
    public function hadCache()
    {
        if(Controller::current()->getMime() === 'html'){
            $this->makeEngine();
            if(!is_subclass_of($this->tplEngine, '\jt\TemplateInterface')){
                throw new Exception('TemplateEngineError:Cache need Template Engine implements \jt\TemplateInterface');
            }
            $uri = Controller::current()->getRequestPath();
            if($this->tplEngine->hadCache($uri, $_SERVER['QUERY_STRING'])){
                return 1;
            }
        }

        return 0;
    }
}