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
    public static function start($sowing = false, $sessionId = '')
    {
        ini_set('session.use_cookies', 0);
        self::checkStatus();
        $sessionId = $sessionId ?: self::getSessionId($sowing);
        if ($sessionId) {
            $handlerClass = '\jt\lib\session\\' . \Config::SESSION['handler'];
            session_set_save_handler(new $handlerClass());
            session_id($sessionId);
        }else {
            session_set_save_handler(new Invalid());
        }

        session_register_shutdown();
        session_start();

        return $sessionId;
    }

    public static function sowing($id)
    {
        $savers = explode(',', \Config::SESSION['idSaver']);
        $names  = explode(',', \Config::SESSION['idName']);

        foreach ($savers as $index => $saver) {
            $name         = $names[$index]??$names[0];
            $sowingMethod = 'sowingIdBy' . $saver;
            if (method_exists(__CLASS__, $sowingMethod)) {
                self::$sowingMethod($id, $name);
            }else {
                throw new TaskException('sessionSowingIdNotExists:存储方式:' . $saver . ' 的存储SessionId的方法未实现');
            }
        }
    }

    protected static function sowingIdByHeader($id, $name)
    {
        $ns = explode('_', $name);
        foreach ($ns as &$n) {
            $n = ucfirst(strtolower($n));
        }
        $name = implode('-', $ns);
        header("{$name}: {$id}");
    }

    protected static function sowingIdByUrl($id, $name)
    {

    }

    protected static function sowingIdByCookie($id, $name)
    {
        //TODO 配置Session Cookie
        $expire = time() + 45 * 60;
        $path = '/';
        $domain = null;
        $secure = null;
        $httpOnly = null;
        setcookie($name, $id, $expire, $path, $domain, $httpOnly);
    }

    public static function erase($sessionId)
    {
        if ($sessionId) {
            self::start(false, $sessionId);
            \session_destroy();
        }
    }

    public static function regenerateId($hold = false, $sessionId = null)
    {
        $data = null;
        if ($hold && \session_status() === PHP_SESSION_ACTIVE) {
            $data = $_SESSION;
        }
        if (!$sessionId) {
            $sessionId = self::genSessionId();
        }
        self::start(false, $sessionId);
        if ($hold && $data) {
            $_SESSION = $data;
        }
    }

    private static function checkStatus()
    {
        switch (\session_status()) {
            case PHP_SESSION_DISABLED:
                throw new TaskException('SessionDisabled:当前SESSION不可用，请打开PHP SESSION功能');
                break;
            case PHP_SESSION_ACTIVE:
                \session_write_close();
                break;
        }
    }

    public static function getSessionId($sowing = false)
    {
        $savers = explode(',', \Config::SESSION['idSaver']);
        $names  = explode(',', \Config::SESSION['idName']);

        foreach ($savers as $index => $saver) {
            $name = $names[$index]??$names[0];
            \session_save_path($saver);
            \session_name($name);
            $getIdMethod = 'getIdBy' . $saver;
            if (method_exists(__CLASS__, $getIdMethod)) {
                $sessionId = self::$getIdMethod($name);
                if ($sessionId) {
                    return $sessionId;
                }
            }else {
                throw new TaskException('sessionSaverNotExists:存储方式:' . $saver . ' 的获取SessionId的方法未实现');
            }
        }
        if ($sowing) {
            return self::genSessionId();
        }

        return '';
    }

    public static function genSessionId()
    {
        $id = Helper::uuid();
        self::sowing($id);

        return $id;
    }

    protected static function getIdByHeader($name)
    {
        return $_SERVER['HTTP_' . $name]??null;
    }

    protected static function getIdByUrl($name)
    {
        return $_GET[$name]??null;
    }

    protected static function getIdByCookie($name)
    {
        $id = $_COOKIE[$name]??null;
        if($id){
            self::sowingIdByCookie($id, $name);//刷新Cookie的有效期
        }
        return $id;
    }

    /**
     * Initialize session
     *
     * @link http://php.net/manual/en/sessionhandlerinterface.open.php
     * @param string $save_path The path where to store/retrieve the session.
     * @param string $session_id The session id.
     * @return bool <p>
     * The return value (usually TRUE on success, FALSE on failure).
     * Note this value is returned internally to PHP for processing.
     * </p>
     * @since 5.4.0
     */
    public function open($save_path, $session_id)
    {
        return true;
    }
}