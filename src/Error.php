<?php
/**
 * Auth: ax
 * Date: 15-4-26 15:39
 */

namespace jt;

use jt\utils\Debug;

class Error extends Action
{
    /**
     * 是否直接输出错误
     *
     * @type bool
     */
    protected static $isDirectOutput = false;
    /**
     * 收集到的错误信息
     *
     * @var array
     */
    protected static $collected = [];

    /**
     * 捕获错误
     *
     * @param       $errNo
     * @param       $errStr
     * @param       $errFile
     * @param       $errLine
     */
    public static function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        $msg = $errStr.' in '.$errFile.' line '.$errLine;
        //写错误日志
        if(in_array($errNo, [
            E_ERROR,
            E_RECOVERABLE_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR
        ])){
            self::exeFatal($errNo, $msg, []);
            self::logFatal($errNo, $msg);
        }elseif(ERRORS_VERBOSE){
            self::notice($errNo, $msg);
            self::logNotice($errNo, $msg);
        }
    }

    /**
     * 写致命错误日志
     *
     * @param $errNo
     * @param $desc
     */
    public static function logFatal($errNo, $desc)
    {
        self::writeLog('['.$errNo.'] '.$desc, 'fatal');
    }

    /**
     * 写错误信息
     *
     * @param $errNo
     * @param $desc
     */
    public static function logNotice($errNo, $desc)
    {
        self::writeLog('['.$errNo.'] '.$desc, 'notice');
    }

    /**
     * 写错误日志
     *
     * @param $desc
     * @param $file
     */
    protected static function writeLog($desc, $file)
    {
        if(!is_dir(\Config::ERROR_LOG_DIR)){
            mkdir(\Config::ERROR_LOG_DIR, 0777, true);
        }
        file_put_contents(\Config::ERROR_LOG_DIR.'/'.$file.'.log', $desc.PHP_EOL, FILE_APPEND);
    }

    /**
     * 捕获异常
     *
     * @param \Exception $e
     */
    public static function exceptionHandler($e)
    {
        $msg  = $e->getMessage();
        $code = $e->getCode();

        $offset = strpos($msg, ':');
        $pos = 0;
        while($offset){
            $pos = 1;
            while(substr($msg, $offset - $pos, 1) === '/'){
                $pos++;
            }
            if($pos % 2 === 1){
                if(substr($msg, $offset+1, 1) === ':' && substr($msg, $offset+2, 1) !== ':'){
                    $offset+=1;
                }else{
                    $code = substr($msg, 0, $offset);
                    $msg = substr($msg, $offset+1);
                    $code = strtr($code, [
                        '/:' => ':'
                    ]);
                    break;
                }
            }
            $offset = strpos($msg, ':', $offset+1);
        }
        if($pos && $pos % 2 === 0){
            $msg = strtr($msg, [
                '/:' => ':'
            ]);
        }

        if($code == 404){
            self::logFatal($code, $msg);
        }

        $data = [];

        if($e instanceof Exception){
            switch($e->getType()){
                case 'taskEnd':
                    Controller::current()->response();

                    return;
                case 'taskFail':
                    break;
                default:
                    if(ERRORS_VERBOSE){
                        $data['_debug_trace'] = $e->getTrace();
                        Controller::current()->getAction()->header('triggerPoint', $e->getFile().' line '.$e->getLine());
                    }
                    break;
            }
            $data = array_merge($data, $e->getData());
            self::error($code, $msg, false, $e->getParam(), $data);
        }else{
            if(ERRORS_VERBOSE){
                $data['_debug_trace'] = $e->getTrace();
                Controller::current()->getAction()->header('triggerPoint', $e->getFile().' line '.$e->getLine());
            }
            self::fatalError($code, $msg, [], true, $data);
        }
    }

    private static function fatalError($code, $msg = '', $param = [], $strict = true, $data = [])
    {
        self::$collected['fatal'] = [
            'code' => $code,
            'msg'  => $msg
        ];
        //$handler = new ErrorHandler();
        \header('Status: 500', true);
        self::error($code, $msg, $strict, $param, $data);
    }

    /**
     * 触发致命错误
     *
     * @param string $code
     * @param string $msg
     * @param array  $param 传递的其它参数
     */
    public static function fatal($code, $msg = '', $param = [])
    {
        self::exeFatal($code, $msg, $param);
        Controller::current()->quiet();
        Responder::end();
    }

    /**
     * 执行致命错误
     *
     * @param        $code
     * @param string $msg
     * @param array  $param
     */
    private static function exeFatal($code, $msg, $param)
    {
        $p = [];
        $d = [];
        foreach($param as $k => $v){
            if(is_string($k)){
                $d[$k] = $v;
            }else{
                $d[] = $v;
            }
        }
        if($d){
            $method = '_error';
            self::getAction($method, true)->outMass($d);
        }
        self::fatalError($code, $msg, $p, false);
    }

    /**
     * 捕获到的错误
     *
     * @param string $code
     * @param string $msg
     * @param bool   $fatal 是否致命错误
     * @param array  $param 传递的其它参数
     * @param array  $data 附加的数据
     */
    protected static function error($code, $msg, $fatal, $param = [], $data = [])
    {
        if(self::$isDirectOutput){
            echo $code.': '.$msg, PHP_EOL;

            return;
        }
        $method = '_'.$code;
        $action = self::getAction($method, $fatal);
        $action->cleanData();
        if(is_numeric($code) && preg_match('/[34]{1}\d{2}/', $code)){
            $action->status(intval($code), $msg, [], false);
        }
        $action->header('code', $code);
        $action->header('msg', $msg);

        if(Controller::current()->getMime() === 'html'){
            $data['title'] = $action->getFromData('title')?: '有错误发生';
            $data['code']  = $code;
            $data['msg']   = $msg;
            Controller::current()->setTemplate('error/error');
        }
        $action->outMass($data);
        $action->$method(...$param);
        try{
            Controller::current()->getResponder()->write();
        }catch(\Exception $e){
            $ruler = Controller::current()->getRuler();
            echo $e->getMessage(), "<br>\n";
            echo $code.':'.$msg, "<br>\n";
            echo "<i>Access entry: {$ruler[0]}::{$ruler[1]} (@router at line: {$ruler[9]})</i>";
        }
    }

    /**
     * 寻找当前的ACTION
     *
     * @param string $method
     * @param bool   $fatal 是否致命错误
     *
     * @return \jt\Action|\jt\ErrorHandler
     */
    final private static function getAction(&$method, $fatal)
    {
        $controller = Controller::current();
        $action     = $controller->getAction();

        if($action && method_exists($action, $method)){
            return $action;
        }

        $data = $action->getDataStore(false);
        /** @var \jt\Action $errorAction */
        $errorAction = null;
        $class       = '\\'.MODULE.'\action\ErrorHandler';
        if(class_exists($class)){
            $action = new $class();
            if(\method_exists($action, $method)){
                $errorAction = $action;
            }
        }

        if($errorAction === null){
            $errorAction = new ErrorHandler();
            if(!\method_exists($action, $method)){
                $method = $fatal? 'unknown_error': 'unknown_fail';
            }
        }

        $controller->setAction($errorAction);
        $errorAction->outMass($data);

        return $errorAction;
    }

    public static function getTrace()
    {
        $trace = debug_backtrace(false, 5);
        array_shift($trace);
        array_shift($trace);
        foreach($trace as $k => $t){
            if(!isset($t['file']) || !isset($t['class'])){
                unset($trace[$k]);
            }
        }

        return $trace;
    }

    /**
     * 收集警告
     *
     * @param $code
     * @param $msg
     */
    public static function notice($code, $msg)
    {
        if(strpos($msg, '/smarty/compile/') || strpos($msg, '/runtime/')){
            return;
        }
        self::$collected['notice'][] = [
            'code' => $code,
            'msg'  => $msg
        ];
        if(self::$isDirectOutput){
            echo $code.': '.$msg, PHP_EOL;
        }
    }

    /**
     * 收集消息
     *
     * @param $code
     * @param $msg
     */
    public static function info($code, $msg)
    {
        self::$collected['info'][] = [
            'code' => $code,
            'msg'  => $msg
        ];
        if(self::$isDirectOutput){
            echo $code.': '.$msg, PHP_EOL;
        }
    }

    /**
     * 准备错误消息
     *
     * @return array
     */
    public static function prepareHeader()
    {
        $success = Controller::current()->isCompleteAndSuccess();
        $header  = [
            'success' => $success,
            'msg'     => $success? '请求成功': '请求失败',
            'code'    => ''
        ];
        if(isset(self::$collected['fatal'])){
            $header = array_merge($header, self::$collected['fatal']);
        }
        if(isset(self::$collected['notice'])){
            $header['notice'] = self::$collected['notice'];
        }
        if(isset(self::$collected['info'])){
            $header['info'] = self::$collected['info'];
        }

        //>debug
        if(RUN_MODE !== 'production'){
            $header['queryCount']   = class_exists('\jt\Model', false)? Model::getQueryTimes(): 0;// + \dal\Dal::selectQueryTimes();
            $header['querySqlList'] = Debug::getFromCollect('sql');

            $includeFiles             = get_included_files();
            $header['loadFilesCount'] = count($includeFiles);

            $unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            $size = memory_get_usage(true) / 8;
            $i    = (int)floor(log($size, 1024));

            $header['useMemory'] = round($size / pow(1024, $i), 2).' '.$unit[$i];
            $header['spendTime'] = round((microtime(true) - Bootstrap::$startTime) * 1000, 3);

            $ruler = Controller::current()->getRuler();
            if(!empty($ruler)){
                $header['entrance'] = "{$ruler[0]}::{$ruler[1]} (@router at line: {$ruler[9]})";
            }
        }

        //debug<

        return $header;
    }

    /**
     * 是否直接输出错误，便于调试
     *
     * @param bool $v
     */
    public static function directOutput($v = true)
    {
        self::$isDirectOutput = RUN_MODE === 'develop'? $v: false;
    }

    /**
     * 清除搜集到的错误
     */
    public function cleanData()
    {
        self::$collected = [];
    }
}