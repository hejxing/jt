<?php
/**
 * Auth: ax@csmall.com
 * Date: 15-4-1
 * Time: 03:15
 *
 * 路由匹配
 */

namespace jt;

/**
 * 任务调度、管控中心，框架核心部件
 * 负责应用路由规则，触发任务执行前、执行后的相关事件，将执行结果进一步加工
 *
 * @package jt
 */
class Controller
{
    /**
     * @type Controller 当前使用的路由
     */
    private static $currController = null;
    /**
     * @type array 路由缓存
     */
    protected static $routerMapPool = [];
    /**
     * @var string 访问路径
     */
    protected $uri = '';
    /**
     * @var array 分解出的访问路径
     */
    protected $paths = [];
    /**
     * @var array 匹配到的路由规则
     */
    protected $ruler = [];
    /**
     * @var \jt\Requester
     */
    protected $requester = null;
    /**
     * @var \jt\Action
     */
    protected $action = null;
    /**
     * @var \jt\Responder
     */
    protected $responder = null;
    /**
     * @type string 执行的方法
     */
    protected $method = '';
    /**
     * @type string 请求的方式
     */
    protected $requestMethod = '';
    /**
     * @var array 路径中的参数
     */
    protected $param = [];
    /**
     * @type string 响应的文档类型
     */
    protected $mime = '';
    /**
     * @type string 生成HTML用的模板文件
     */
    protected $template = '';
    /**
     * @type \jt\auth\Auth 权限控制器
     */
    protected $authority = null;
    /**
     * @var \jt\log\Writer;
     */
    protected $logWriter = null;
    /**
     * @type int 重试的次数
     */
    private static $retryTimes = 10;
    /**
     * @type bool 是否允许输出
     */
    private $outputAllow = true;
    /**
     * @type bool 是否需要重试
     */
    private $isNeedRetry = false;
    /**
     * @var array 存储设的勾子
     */
    private $hookQueue = [
        'auth'    => [],
        'execute' => [],
        'render'  => []
    ];

    /**
     * 匹配访问入口
     *
     * @param array $server 请求相关选项
     *
     * @throws \Exception
     */
    public function __construct(array $server)
    {
        $this->uri            = $server['SCRIPT_NAME'];
        $this->requestMethod  = strtolower($server['REQUEST_METHOD']);
        self::$currController = $this;
    }

    /**
     * 启动控制器
     */
    public function run()
    {
        $this->cutURI();
        $this->dispatch();
        $this->execute();
        if($this->isNeedRetry){
            $this->retry();
        }
        $this->response();
    }

    /**
     * 遇到错误，错误解决后重新尝试执行
     */
    public function retry()
    {
        $this->isNeedRetry = false;
        self::$retryTimes--;
        if(self::$retryTimes > 0){
            $this->action->cleanData();
            //Error::cleanData();

            Model::rollBackAll();
            $this->execute();
        }
    }

    /**
     * 获取权限验证器
     *
     * @param $author
     *
     * @return bool
     */
    private function checkAuthority($author)
    {
        list($className, $mark) = $author;

        if($className === 'public'){
            return true;
        }

        $this->authority = new $className();
        $this->authority->setMark($mark);

        return $this->authority->inCheck();
    }

    /**
     * @param array $info
     * @return \jt\log\Writer
     */
    public function getLogWriter(array $info = []){
        if($this->logWriter === null){
            $writerClass = $this->ruler[7];
            $this->logWriter = new $writerClass(array_replace([

            ], $info));
        }

        foreach($info as $name => $value){
            $this->logWriter->set($name, $value);
        }

        return $this->logWriter;
    }

    /**
     * 过滤返回结果
     *
     * @return bool
     */
    private function applyFilter()
    {
        if($this->authority === null){
            return true;
        }

        return $this->authority->outCheck();
    }

    /**
     * 触发钩子
     *
     * @param $on
     * @return bool
     */
    private function trigger($on)
    {
        foreach($this->hookQueue[$on] as $task){
            if(call_user_func_array($task[0], $task[1]) === false){
                return false;
            }
        }

        return true;
    }

    /**
     * 设置构子
     *
     * @param string   $on 运行时机
     * @param callable $callable
     * @param array    $param
     */
    public function hook($on, $callable, array $param = [])
    {
        $this->hookQueue[$on][] = [$callable, $param];
    }

    /**
     * 执行接口
     */
    private function execute()
    {
        if($this->action === null){
            return;
        }

        if(strpos($this->ruler[8], 'output_quiet') !== false){
            $this->action->quiet();
        }

        if($this->action->init() === false){
            throw new Exception('Action '.$this->ruler[0].' init fail', 500);
        }

        if($this->trigger('auth') === false){
            return;
        }

        if($this->checkAuthority($this->ruler[4]) === false){
            return;
        }

        if(call_user_func_array([$this->action, 'before'], [$this->method, $this->param]) === false){
            return;
        }

        if($this->trigger('execute') === false){
            return;
        }

        $result = call_user_func_array([$this->action, $this->method], $this->param);
        if(is_array($result)){
            if(isset($result[0]) && is_int($result[0])){
                $this->action->status($result[0], '', array_slice($result, 1));
            }

            $this->action->outMass($result);
        }

        if(call_user_func_array([$this->action, 'after'], [$this->method, $this->param]) === false){
            return;
        }


        if($this->applyFilter() === false){
            return;
        }

        if($this->trigger('render') === false){
            return;
        }
        $this->action->setIsRunComplete(true);
    }

    /**
     * 获取当前路由
     *
     * @return Controller
     */
    public static function current()
    {
        if(self::$currController === null){
            self::$currController = new self($_SERVER);
        }

        return self::$currController;
    }

    /**
     * 获取当前的接口实例
     *
     * @return \jt\Action
     */
    public function getAction()
    {
        if($this->action === null){
            $action = new Action();
            $action->setController($this);
            $this->action = $action;
        }
        return $this->action;
    }

    /**
     * 设置当前的接口实例
     *
     * @param Action $action
     */
    public function setAction(Action $action)
    {
        $this->action = $action;
    }

    /**
     * 获取本次执行的方法
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 获取请求的路径
     *
     * @return string
     */
    public function getRequestPath()
    {
        return '/'.implode('/', $this->paths);
    }

    /**
     * 获取本次请求的方式
     *
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * 获取执行时用的参数
     *
     * @return array
     */
    public function getParam()
    {
        return $this->param;
    }


    /**
     * 分解访问路径
     */
    protected function cutURI()
    {
        $this->paths = \explode('/', \trim($this->uri, '/')); //去掉第一个斜杠
        $this->parseMime();
    }

    /**
     * 解析后缀
     * TODO: 接受 accept-type
     */
    protected function parseMime()
    {
        $last = array_pop($this->paths);
        if($last === null){
            return;
        }
        $pos = strrpos($last, '.');
        if($pos !== false && ($suffix = substr($last, $pos + 1)) && $this->setMime($suffix)){
            if($pos === 0){
                $last = 'index';
            }else{
                $last = substr($last, 0, $pos);
            }
        }
        $this->paths[] = $last;
    }

    /**
     * 设置输出的文档类型
     *
     * @param string $mime 文档类型
     * @return bool
     */
    public function setMime($mime)
    {
        if(in_array($mime, \Config::ACCEPT_MIME)){
            $this->mime = $mime;

            return true;
        }

        return false;
    }


    /**
     * 获取回应的格式
     *
     * @return string
     */
    public function getMime()
    {
        return $this->mime?: \Config::ACCEPT_MIME[0];
    }

    /**
     * 加载并实例化接口
     *
     * @param string $class Action Class
     * @param string $method
     * @return bool
     *
     * @throws Exception
     */
    protected function loadAction($class, $method)
    {
        if(class_exists($class)){
            /** @type Action $action */
            $action = new $class();
            $action->setController($this);
        }else{
            //@i18n msg:'Action {$class} not found'
            //throw new Exception(I18n::speak('error.actionNotFound', ['class' => $class]), 404);
            throw new Exception('Router not found ['.$class.']', 404);
        }

        if(method_exists($action, $method)){
            $this->action = $action;
            $this->method = $method;

            return true;
        }else{
            throw new Exception('Method not found ['.$class.'/:/:'.$method.']', 404);
        }
    }

    /**
     * 加载路由规则
     *
     * @return array
     */
    protected static function loadRouter()
    {
        if(isset(self::$routerMapPool[MODULE])){
            return self::$routerMapPool[MODULE];
        }
        $routerMapFile = RUNTIME_PATH_ROOT.'/router/'.MODULE.'.php';
        //加载不成功则生成
        if(RUN_MODE === 'develop'){ //生成路由
            $result = compile\router\Router::general($routerMapFile, PROJECT_ROOT, MODULE);
        }else{ //加载路由
            $result = include($routerMapFile);
            if($result === false){ //生成并保存
                $result = compile\router\Router::general($routerMapFile, PROJECT_ROOT, MODULE);
            }
        }
        if(!is_array($result)){
            Error::fatal('LoadRouterMapFail', '加载或生成路由表失败');
        }
        self::$routerMapPool[MODULE] = $result;

        return $result;
    }

    public static function getRouterMap()
    {
        return self::$routerMapPool;
    }

    /**
     * 匹配路径
     */
    protected function dispatch()
    {
        //加载配置
        $router            = static::loadRouter();
        $param             = [];
        $anyMatch          = [];
        $startAnyMathIndex = 0;
        //匹配路由
        foreach($this->paths as $index => $p){
            if(isset($router['__*'])){
                $anyMatch          = $router['__*'];
                $startAnyMathIndex = $index;
            }
            if(isset($router[$p])){
                $router = $router[$p];
            }elseif(isset($router['__var'])){ //参数
                $param[] = $p;
                $router  = $router['__var'];
            }else{
                $router = [];
                break;
            }
        }

        $method = $this->requestMethod;

        //检查是否是一个有效的$router
        if(!isset($router['__method']) && !isset($anyMatch['__method'])){
            //判断是否可以补'/'
            //if($this->getMime() === 'html' && $method === 'get' && $p !== 'index' && $index + 1 === \count($this->paths)){
            //    $router = $router['index']??$router['__*']??[];
            //    if(isset($router['__method']) && (isset($router['__method'][$method]) || isset($router['__method']['any']))){
            //        Responder::redirect($this->uri.'/');
            //    }
            //}

            throw new Exception('404:Router not found ['.$this->uri.']');
        }

        if(isset($router['__method'][$method])){
            $this->ruler = $router['__method'][$method];
        }else{
            if(isset($router['__method']['any'])){
                $this->ruler = $router['__method']['any'];
            }else{
                if(isset($anyMatch['__method'][$method])){
                    $this->ruler = $anyMatch['__method'][$method];
                }elseif(isset($anyMatch['__method']['any'])){
                    $this->ruler = $anyMatch['__method']['any'];
                }
                if($this->ruler){
                    $param   = array_slice($param, $startAnyMathIndex);
                    $param[] = implode('/', array_slice($this->paths, $startAnyMathIndex));
                }else{
                    //TODO 列出相关的路由及入口
                    throw new Exception('405:Method not allowed ['.$method.']');
                }
            }
        }

        $this->checkMime();
        $this->loadAction($this->ruler[0], $this->ruler[1]);
        $this->combParam($param, $this->ruler[2]);
    }

    /**
     * 检查当前请求的Mime
     */
    private function checkMime()
    {
        //应用Mime
        if($this->mime){
            if($this->ruler[5] && !in_array($this->mime, $this->ruler[5])){
                throw new Exception('404:Mime not allowed ['.$this->mime.']');
            }
        }else{
            $mimes = $this->ruler[5]?: \Config::ACCEPT_MIME;
            foreach($mimes as $mime){
                if($this->setMime($mime) === true){
                    return;
                }
            }
        }

    }

    /**
     * 匹配,过滤参数
     *
     * @param       $p
     * @param array $options
     */
    private function combParam(array $p, array $options)
    {
        foreach($options as $name => $option){
            switch($option[0]){
                case '\jt\Requester':
                    $this->param[] = Requester::createFromRequest($option[3], $name);
                    break;
                case '\jt\Cookie':
                    $this->param[] = Cookie::create(\Config::COOKIE);
                    break;
                case 'array':
                    Session::start();
                    $this->param[] = $_SESSION;
                    break;
                case 'inject':
                    $class = $option[1];
                    $ruler = $option[2];
                    $param = [];
                    if(isset($ruler['param'])){
                        $param = $ruler['param'];
                    }
                    if(isset($ruler['instance'])){
                        $instance      = $ruler['instance'];
                        $this->param[] = $class::$instance(...$param);
                    }else{
                        $this->param[] = new $class(...$param);
                    }
                    break;
                default:
                    $v = $p[$option[4]];
                    if($v === 'index' && isset($option[2]['require'])){
                        $v = null;
                    }
                    $this->param[] = Requester::validate($v, $option[2], 'path: .'.$name);
                    break;
            }
        }
        Requester::cleanOriginRequest();
    }

    /**
     * 获取控制规则
     *
     * @return array
     */
    public function getRuler()
    {
        return $this->ruler;
    }

    /**
     * 获取模板文件
     *
     * @return string
     */
    public function getTemplate()
    {
        if($this->template){
            $template = $this->template;
        }else{
            $template = $this->ruler[3];
        }
        if(strpos($template, '/') !== 0){
            $template = '/'.$template;
        }

        return $template;
    }

    /**
     * 设置模板文件
     *
     * @param $file
     */
    public function setTemplate($file)
    {
        $this->template = $file;
    }

    /**
     * 是否不允许输出
     *
     * @param bool|true $quiet
     */
    public function quiet($quiet = true)
    {
        $this->outputAllow = !$quiet;
    }

    /**
     * 工作完成,回收资源
     */
    public function __destruct()
    {
        $this->action = null;
    }

    /**
     * 标记为需要进行重试,当在执行完成时将再次重新执行本次请求，一次请求重试次数由retryTimes控制
     */
    public function needRetry()
    {
        $this->isNeedRetry = true;
    }

    /**
     * 获取本次操作的操作员信息
     *
     * return \jt\auth\Operator
     */
    public function getOperator()
    {
        if($this->authority){
            return $this->authority->getOperator();
        }else{
            return new auth\Operator('public', '', '');
        }
    }

    /**
     * 获取权限控制器
     * @return \jt\auth\Auth
     */
    public function getAuthority(){
        return $this->authority;
    }

    /**
     * 获取请求对象
     */
    public function getRequester()
    {
        if($this->requester === null){
            $this->requester = new Requester();
        }

        return $this->requester;
    }

    /**
     * @return Responder
     */
    public function getResponder()
    {
        if($this->responder === null){
            $this->responder = new Responder($this);
        }

        return $this->responder;
    }

    /**
     * 输出请求内容
     */
    public function response()
    {
        if($this->outputAllow){
            $this->getResponder()->write();
        }
    }

    /**
     * 当前任务是否执行成功
     *
     * @return bool
     */
    public function isCompleteAndSuccess(): bool
    {
        return $this->action && $this->action->isSuccess() && $this->action->isRunComplete();
    }
}