<?php
/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/3/25 15:17
 */

namespace jt;

use jt\lib\session\Invalid;
use jt\lib\session\Saver;
use jt\utils\Helper;

abstract class Session
{
    /**
     * 开启会话，会根据配置自动获取会话标识，将会话内容充入$_SESSION
     *
     * @param bool   $sowing
     * @param string $sessionId
     * @param bool   $restart 如果当前会话处于活动状态是否关停
     * @param bool   $holdData 是否保持旧数据
     * @return string
     * @throws Exception
     */
    public static function start($sowing = false, $sessionId = '', $restart = false, $holdData = false)
    {
        ini_set('session.use_cookies', 0);
        $sessionId = $sessionId?: self::getSessionId($sowing, $restart);
        if(!self::checkStatus($restart, $holdData)){
            return $sessionId;
        }

        if($sessionId){
            $handler = new Saver();
            session_set_save_handler($handler);
            session_id($sessionId);
        }else{
            session_set_save_handler(new Invalid());
        }

        session_register_shutdown();
        session_start();

        return $sessionId;
    }

    /**
     * 将会话标识传递给客户端
     *
     * @param $id
     * @throws Exception
     */
    public static function sowing($id)
    {
        $savers = explode(',', \Config::SESSION['idSaver']);
        $names  = explode(',', \Config::SESSION['idName']);

        foreach($savers as $index => $saver){
            $name         = $names[$index]??$names[0];
            $sowingMethod = 'sowingIdBy'.$saver;
            if(method_exists(__CLASS__, $sowingMethod)){
                self::$sowingMethod($id, $name);
            }else{
                throw new Exception('sessionSowingIdNotExists:存储方式:'.$saver.' 的存储SessionId的方法未实现');
            }
        }
    }

    /**
     * 通过Header传递会话ID
     *
     * @param $id
     * @param $name
     */
    protected static function sowingIdByHeader($id, $name)
    {
        $ns = explode('_', $name);
        foreach($ns as &$n){
            $n = ucfirst(strtolower($n));
        }
        $name = implode('-', $ns);
        header("{$name}: {$id}");
    }

    /**
     * 通过Url传递会话ID
     *
     * @param $id
     * @param $name
     */
    protected static function sowingIdByUrl($id, $name)
    {

    }

    /**
     * 通过Cookie传递会话ID
     *
     * @param $id
     * @param $name
     */
    protected static function sowingIdByCookie($id, $name)
    {
        //TODO 配置Session Cookie
        $expire   = 0;//time() + 45 * 60;
        $path     = '/';
        $domain   = null;
        $secure   = $_SERVER['HTTPS']??false;
        $httpOnly = true;
        setcookie($name, $id, $expire, $path, $domain, $secure, $httpOnly);
    }

    /**
     * 清除该会话ID下的会话
     *
     * @param $sessionId
     */
    public static function erase($sessionId = null)
    {
        if($sessionId){
            self::start(false, $sessionId);
        }else{
            self::start();
        }
        session_destroy();
    }

    /**
     * 重新生成会话ID
     *
     * @param bool $hold
     * @param null $sessionId
     */
    public static function regenerateId($hold = false, $sessionId = null)
    {
        $data = null;
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

    /**
     * 检查会话状态
     *
     * @param bool $restart 是否重启新会话
     * @param bool $holdData 是否保持旧数据
     * @return bool
     * @throws Exception
     */
    private static function checkStatus($restart, $holdData)
    {
        $status = session_status();
        switch($status){
            case PHP_SESSION_DISABLED:
                throw new Exception('SessionDisabled:当前SESSION不可用，请打开PHP SESSION功能');
                break;
            case PHP_SESSION_ACTIVE:
                if($restart){
                    if($holdData){
                        session_write_close();
                    }
                }else{
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * 获取会话ID
     *
     * @param bool $sowing
     * @param bool $restart
     * @return string
     * @throws Exception
     */
    public static function getSessionId($sowing = false, $restart = false)
    {

        $savers = explode(',', \Config::SESSION['idSaver']);
        $names  = explode(',', \Config::SESSION['idName']);

        if($sowing && $restart){
            return self::genSessionId();
        }

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
                throw new Exception('sessionSaverNotExists:存储方式:'.$saver.' 的获取SessionId的方法未实现');
            }
        }
        if($sowing){
            return self::genSessionId();
        }

        return '';
    }

    /**
     * 生成会话ID
     *
     * @return string
     * @throws Exception
     */
    public static function genSessionId()
    {
        $id = Helper::uuid().Helper::uuid();
        self::sowing($id);

        return $id;
    }

    /**
     * 通过Header获取会话ID
     *
     * @param $name
     * @return null
     */
    protected static function getIdByHeader($name)
    {
        return $_SERVER['HTTP_'.$name]??null;
    }

    /**
     * 通过Url获取会话ID
     *
     * @param $name
     * @return null
     */
    protected static function getIdByUrl($name)
    {
        return $_GET[$name]??null;
    }

    /**
     * 通过Cookie获取会话ID
     *
     * @param $name
     * @return null
     */
    protected static function getIdByCookie($name)
    {
        //TODO 记录下可疑攻击行为
        if($_SERVER['REQUEST_METHOD'] !== 'GET'){//判断是否同源 防CSRF攻击
            if(!isset($_SERVER['HTTP_REFERER'])){//referer为空
                return null;
            }
            $refererInfo = parse_url($_SERVER['HTTP_REFERER']);

            if($_SERVER['HTTP_HOST'] !== $refererInfo['host'] || $_SERVER['SERVER_PORT'] !== ($refererInfo['port']??'80')){
                return null;
            }
        }

        $id = $_COOKIE[$name]??null;

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

    public function get($name)
    {

    }
}