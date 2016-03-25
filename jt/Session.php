<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2016/3/25 15:17
 */

namespace jt;


use jt\exception\TaskException;
use jt\lib\session\Invalid;
use SessionHandlerInterface;

abstract class Session implements SessionHandlerInterface
{
    public static function start(){
        $sessionId = self::getSessionId();

        if($sessionId){
            $handlerClass = '\jt\lib\session\\'.\Config::SESSION['handler'];
            \session_set_save_handler(new $handlerClass());
            \session_id('session_'.$sessionId);
        }else{
            \session_set_save_handler(new Invalid());
        }

        \session_register_shutdown();
        \session_start();
    }

    private static function getSessionId(){
        $savers = explode(',', \Config::SESSION['idSaver']);
        $names = explode(',', \Config::SESSION['idName']);

        foreach($savers as $index => $saver){
            $name = $names[$index]??$names[0];
            \session_save_path($saver);
            \session_name($name);
            $saverMethod = 'getIdBy'.$saver;
            if(method_exists(__CLASS__, $saverMethod)){
                $sessionId = self::$saverMethod($name);
                if($sessionId){
                    return $sessionId;
                }
            }else{
                throw new TaskException('sessionSaverNotExists:Session_id存储方式:'.$saver.'未实现');
            }
        }
        return null;
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