<?php
/**
 * Created by PhpStorm.
 * User: 渐兴
 * Date: 15-4-26
 * Time: 15:39
 */

namespace jt;

use jt\exception\TaskException;

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
        if (in_array($errNo, [
            E_ERROR,
            E_RECOVERABLE_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR
        ])) {
            self::fatal('FatalError: ' . $errNo, $errStr . ' in ' . $errFile . ' line ' . $errLine);
        }elseif (ERRORS_VERBOSE) {
            self::notice($errNo, $errStr . ' in ' . $errFile . ' line ' . $errLine);
        }
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
        if (strpos($msg, ':') !== false) {
            list($code, $msg) = explode(':', $msg, 2);
        }

        $data = [];
        if(ERRORS_VERBOSE){
            $data['_debug_trace'] = $e->getTrace();
            Controller::current()->getAction()->header('triggerPoint', $e->getFile() . ' line ' . $e->getLine());
        }

        if ($e instanceof TaskException) {
            if ($e->getType() === 'taskEnd') {

                return;
            }
            $data = array_merge($data, $e->getData());
            self::error($code, $msg, false, $e->getParam(), $data);
        }else {
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
     * 产生致命错误
     *
     * @param string $code
     * @param string $msg
     * @param array  $param 传递的其它参数
     */
    public static function fatal($code, $msg = '', $param = [])
    {
        $p = [];
        $d = [];
        foreach ($param as $k => $v) {
            if (is_string($k)) {
                $d[$k] = $v;
            }else {
                $d[] = $v;
            }
        }
        if ($d) {
            $method = '_error';
            self::getAction($method, true)->outMass($d);
        }
        self::fatalError($code, $msg, $p, false);
        Responder::end();
    }

    /**
     * 捕获到的错误
     *
     * @param        $code
     * @param string $msg
     * @param bool   $fatal 是否致命错误
     * @param array  $param 传递的其它参数
     * @param array  $data 附加的数据
     */
    protected static function error($code, $msg, $fatal, $param = [], $data = [])
    {
        $method = '_' . $code;
        $action = self::getAction($method, $fatal);
        $action->header('code', $code);
        $action->header('msg', $msg);
        if (Controller::current()->getMime() === 'html') {
            $action->out('title', $action->getFromData('title') ?: '有错误发生');
            $action->out('code', $code);
            $action->out('msg', $msg);
            Controller::current()->setTemplate('error/error');
        }
        $action->outMass($data);
        $action->$method(...$param);
        try{
            Responder::write();
        }catch (\Exception $e){
            echo $e->getMessage(), "<br>\n";
            echo $code . '::' . $msg;
        }
    }

    /**
     * 寻找当前的ACTION
     *
     * @param string $method
     * @param bool   $fatal 是否致命错误
     *
     * @return \jt\Controller|\jt\ErrorHandler
     */
    final private static function getAction(&$method, $fatal)
    {
        $controller = Controller::current();
        $action     = $controller->getAction();
        if ($action && method_exists($action, $method)) {
            return $action;
        }

        $class = MODULE_NAMESPACE_ROOT . '\action\ErrorHandler';
        if (Bootstrap::tryLoad($class)) {
            $action = new $class();
            if (\method_exists($action, $method)) {
                return $action;
            }
        }

        $action = new ErrorHandler();
        if (!\method_exists($action, $method)) {
            $method = $fatal ? 'unknown_error' : 'unknown_fail';
        }

        return $action;
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