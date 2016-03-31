<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2016/3/25 15:17
 */

namespace jt;


use jt\exception\TaskException;
use jt\lib\session\Invalid;
use jt\utils\Helper;
use SessionHandlerInterface;

abstract class Session implements SessionHandlerInterface
{
    public static function start($sowing = false, $sessionId = ''){
        self::checkStatus();
        $sessionId = $sessionId?:self::getSessionId($sowing);
        if($sessionId){
            $handlerClass = '\jt\lib\session\\'.\Config::SESSION['handler'];
            \session_set_save_handler(new $handlerClass());
            \session_id($sessionId);
        }else{
            \session_set_save_handler(new Invalid());
        }

        \session_register_shutdown();
        \session_start();
        return $sessionId;
    }

    public static function erase($sessionId){
        if($sessionId){
            self::start(false, $sessionId);
            \session_destroy();
        }
    }
    
    public static function regenerateId($hold = false, $sessionId = null){
        if($hold && \session_status() === PHP_SESSION_ACTIVE){
            $data = $_SESSION;
        }
        if(!$sessionId){
            $sessionId = self::genSessionId();
        }
        self::start(false, $sessionId);
        if($hold && $data){
            $_SESSION = $data;
        }
    }

    private static function checkStatus(){
        switch(\session_status()){
            case PHP_SESSION_DISABLED:
                throw new TaskException('SessionDisabled:当前SESSION不可用，请打开PHP SESSION功能');
                break;
            case PHP_SESSION_ACTIVE:
                \session_write_close();
                break;
        }
    }

    public static function getSessionId($sowing = false){
        $savers = explode(',', \Config::SESSION['idSaver']);
        $names = explode(',', \Config::SESSION['idName']);

        foreach($savers as $index => $saver){
            $name = $names[$index]??$names[0];
            \session_save_path($saver);
            \session_name($name);
            $getIdMethod = 'getIdBy'.$saver;
            if(method_exists(__CLASS__, $getIdMethod)){
                $sessionId = self::$getIdMethod($name);
                if($sessionId){
                    return $sessionId;
                }
            }else{
                throw new TaskException('sessionSaverNotExists:存储方式:'.$saver.' 的获取SessionId的方法实现');
            }
        }
        if($sowing){
            return self::genSessionId();
        }
        return '';
    }

    public static function genSessionId(){
        return Helper::uuid();
    }

    protected static function getIdByHeader($name){
        return $_SERVER['HTTP_'.$name]??null;
    }

    protected static function getIdByCookie($name){

    }

    protected static function getIdByUrl($name){
        return $_GET[$name]??null;
    }

    /**
     * Initialize session
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $session_id The session id.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $session_id){
        return true;
    }
}