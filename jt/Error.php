<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 15:39
 */

namespace jt;

class Error extends Action
{
    /**
     * 收集到的错误信息
     *
     * @var array
     */
    static protected $collected = [];

    /**
     * 捕获错误
     *
     * @param       $errNo
     * @param       $errStr
     * @param       $errFile
     * @param       $errLine
     */
    static public function errorHandler($errNo, $errStr, $errFile, $errLine)
    {
        //写错误日志
        if ($errNo === E_ERROR) {
            self::fatal('FatalError: ' . $errNo, $errStr . ' in ' . $errFile . ' on line ' . $errLine);
        }elseif (RUN_MODE === 'develop') {
            self::notice($errNo, $errStr . ' in ' . $errFile . ' on line ' . $errLine, AL);
            //Controller::current()->getAction()->header('notice', $errStr . ' in ' . $errFile . ' on line ' . $errLine, AL);
        }
    }

    /**
     * 捕获异常
     *
     * @param \Exception $e
     */
    static public function exceptionHandler($e)
    {
        //if($e instanceof \ErrorException || $e instanceof \PDOException){
        $msg  = $e->getMessage();
        $code = $e->getCode();
        if (strpos($msg, ':') !== false) {
            list($code, $msg) = explode(':', $msg, 2);
        }
        self::fatal($code, $msg);
        //}else{
        //	self::notice($e->getCode(), $e->getMessage());
        //}
    }

    /**
     * 产生致命错误
     *
     * @param string $code
     * @param string $msg
     * @param array  $param 传递的其它参数
     */
    public static function fatal($code, $msg = '', $param = [])
    {
        self::$collected['fatal'] = [
            'code' => $code,
            'msg'  => $msg
        ];
        //$handler = new ErrorHandler();
        self::error($code, $msg, true, $param);
    }

    /**
     * 产生错误消息
     *
     * @param string $code
     * @param string $msg
     * @param array  $data
     */
    public static function msg($code, $msg = '', array $data = [])
    {
        $method = '_notice';
        self::getAction($method)->outMass($data);
        self::error($code, $msg, false);
    }

    /**
     * 捕获到的错误
     *
     * @param        $code
     * @param string $msg
     * @param bool   $fatal 是否致命错误
     * @param array  $param 传递的其它参数
     */
    protected static function error($code, $msg, $fatal, $param = [])
    {
        if ($fatal) {
            $method = '_' . $code;
        }else {
            $method = 'client_error';
        }

        $action = self::getAction($method);
        $action->header('code', $code);
        $action->header('msg', $msg);
        if (Controller::current()->getMime() === 'html') {
            $action->out('title', $action->getFromData('title') ?: '有错误发生');
            $action->out('code', $code);
            $action->out('msg', $msg);
            Controller::current()->setTemplate('error/error');
        }
        if ($fatal && RUN_MODE === 'develop') {
            $action->out('trace', self::getTrace());
        }
        $action->$method(...$param);
        Responder::write();
        //致命错误
        //if($fatal){
        die();
        //}
    }

    /**
     * 寻找当前的ACTION
     *
     * @param string $method
     *
     * @return \jt\Controller|\jt\ErrorHandler
     */
    final private static function getAction(&$method)
    {
        $controller = Controller::current();
        $action     = $controller->getAction();
        if ($action && method_exists($action, $method)) {
            return $action;
        }

        if (!$controller->loadAction('\app\\' . MODULE . '\action\ErrorHandler', $method)) {
            if (!$controller->loadAction('\jt\ErrorHandler', $method) && $method !== 'unkown_error') {
                $method = 'unkown_error';

                return self::getAction($method);
            }
        }

        return $controller->getAction();
    }

    static public function getTrace()
    {
        $trace = debug_backtrace(false, 5);
        array_shift($trace);
        array_shift($trace);
        foreach ($trace as $k => $t) {
            if (!isset($t['file']) || !isset($t['class'])) {
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
    static public function notice($code, $msg)
    {
        self::$collected['notice'][] = [
            'code' => $code,
            'msg'  => $msg
        ];
    }

    /**
     * 收集消息
     *
     * @param $code
     * @param $msg
     */
    static public function info($code, $msg)
    {
        self::$collected['info'][] = [
            'code' => $code,
            'msg'  => $msg
        ];
    }

    /**
     * 准备错误消息
     *
     * @return array
     */
    static public function prepareHeader()
    {
        $success = Action::isSuccess() && Action::isRunComplete();
        $header  = [
            'success' => $success,
            'msg'     => $success ? '请求成功' : '请求失败'
        ];
        if (isset(self::$collected['fatal'])) {
            $header = array_merge($header, self::$collected['fatal']);
        }
        if (isset(self::$collected['notice'])) {
            $header['notice'] = self::$collected['notice'];
        }
        if (isset(self::$collected['info'])) {
            $header['info'] = self::$collected['info'];
        }

        return $header;
    }
}