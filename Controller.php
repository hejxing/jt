<?php
/**
 * Auth: ax@jentian.com
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
     * 当前使用的路由
     *
     * @type null
     */
    private static $controller = null;
    /**
     * 路由缓存
     *
     * @type array
     */
    private static $routerMapPool = [];
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
     * @var \jt\Action
     */
    protected $action = null;
    /**
     * 执行的方法
     *
     * @type string
     */
    protected $method = '';
    /**
     * 请求的方式
     *
     * @type string
     */
    protected $requestMethod = '';
    /**
     * @var array 路径中的参数
     */
    protected $param = [];
    /**
     * 响应的文档类型
     *
     * @type string
     */
    protected $mime = '';
    /**
     * 生成HTML用的模板文件
     *
     * @type string
     */
    protected $template = '';
    /**
     * 权限控制器
     *
     * @type \jt\Auth
     */
    protected $authority = null;
    /**
     * 重试的次数
     *
     * @type int
     */
    private static $retryTimes = 0;
    /**
     * @type bool 是否允许输出
     */
    private $outputAllow = true;
    /**
     * 是否需要重试
     * @type bool
     */
    private $isNeedRetry = false;

    /**
     * 匹配访问入口
     *
     * @param string $uri 请求地址
     *
     * @throws \Exception
     */
    private function __construct($uri)
    {
        self::$controller = $this;
        $this->uri        = $uri;
    }

    /**
     * 启动控制器
     *
     * @param string $uri 路径
     * @return Controller
     */
    public static function run($uri)
    {
        $c = new self($uri);
        $c->cutURI();
        $c->dispatch();
        $c->execute();
        if($c->isNeedRetry){
            $c->retry();
        }
        if ($c->outputAllow) {
            Responder::write();
        }

        return $c;
    }

    /**
     * 遇到错误，错误解决后重新尝试执行
     */
    public function retry()
    {
        $this->isNeedRetry = false;
        self::$retryTimes++;
        if (self::$retryTimes <= 10) {
            Action::cleanData();
            Error::cleanData();
            
            Model::rollBack();
            $this->execute();
        }
    }

    /**
     * 获取权限验证器
     *
     * @param $className
     *
     * @return bool
     */
    private function checkAuthority($className)
    {
        if (!$className) {
            $className = \Config::DEFAULT_AUTH_CHECKER;
        }
        if ($className === 'public') {
            return true;
        }

        $this->authority = new $className();
        return $this->authority->check();
    }

    /**
     * 过滤返回结果
     *
     * @return bool
     */
    private function applyFilter()
    {
        if ($this->authority === null) {
            return true;
        }

        return $this->authority->filter();
    }

    /**
     * 执行接口
     */
    private function execute()
    {
        if ($this->action === null) {
            return;
        }

        if (strpos($this->ruler[7], 'output_quiet') !== false) {
            $this->action->quiet();
        }

        if ($this->checkAuthority($this->ruler[4]) === false) {
            return;
        }

        if (\call_user_func_array([$this->action, 'before'], [$this->method, $this->param]) === false) {
            return;
        }

        $result = \call_user_func_array([$this->action, $this->method], $this->param);
        if (\is_array($result)) {
            if (isset($result[0]) && \is_int($result[0])) {
                $this->action->status($result[0], array_slice($result, 1));
            }
            $this->action->outMass($result);
        }

        if ($this->applyFilter() === false) {
            return;
        }

        if (\call_user_func_array([$this->action, 'after'], [$this->method, $this->param]) === false) {
            return;
        }

        Action::setIsRunComplete(true);
    }

    /**
     * 获取当前路由
     *
     * @return Controller
     */
    public static function current()
    {
        if (self::$controller === null) {
            return new self('');
        }

        return self::$controller;
    }

    /**
     * 获取当前的接口实例
     *
     * @return \jt\Action
     */
    public function getAction()
    {
        if($this->action === null){
            return new Action();
        }
        return $this->action;
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
        if ($last === null) {
            return;
        }
        $pos = strrpos($last, '.');
        if ($pos !== false && ($suffix = substr($last, $pos + 1))) {
            if ($this->setMime($suffix) === false) {
                Error::fatal('404', 'Mime: [' . $suffix . '] not exists');
            }
            $last = substr($last, 0, $pos);
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
        if (in_array($mime, \Config::ACCEPT_MIME)) {
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
        return $this->mime ?: \Config::ACCEPT_MIME[0];
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
    public function loadAction($class, $method)
    {
        if (class_exists($class)) {
            /** @type Action $action */
            $action = new $class();
        }else {
            throw new Exception('Action ' . $class . ' not found', 404);
        }

        if ($action->init() === false) {
            throw new Exception('Action ' . $class . ' init fail', 500);
        }

        if (method_exists($action, $method)) {
            $this->action = $action;
            $this->method = $method;

            return true;
        }else {
            throw new Exception('Method not found ' . $class . '::' . $method, 404);
        }
    }

    /**
     * 加载路由规则
     *
     * @return array
     */
    private static function loadRouter()
    {
        if (isset(self::$routerMapPool[MODULE])) {
            return self::$routerMapPool[MODULE];
        }
        $routerMapFile = \Config::RUNTIME_PATH_ROOT . '/router/' . MODULE . '.php';
        //加载不成功则生成
        if (RUN_MODE === 'develop') { //生成路由
            $result = compile\router\Router::general($routerMapFile);
        }else { //加载路由
            $result = include($routerMapFile);
            if ($result === false) { //生成并保存
                $result = compile\router\Router::general($routerMapFile);
            }
        }
        if (!is_array($result)) {
            Error::fatal('LoadRouterMapFail', '加载或生成路由表失败');
        }
        self::$routerMapPool[MODULE] = $result;

        return $result;
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
        foreach ($this->paths as $index => $p) {
            if (isset($router['__*'])) {
                $anyMatch          = $router['__*'];
                $startAnyMathIndex = $index;
            }
            if (isset($router[$p])) {
                $router = $router[$p];
            }elseif (isset($router['__var'])) { //参数
                $param[] = $p;
                $router  = $router['__var'];
            }else {
                $router = [];
                break;
            }
        }

        $method = strtolower($_SERVER['REQUEST_METHOD']);
        //检查是否是一个有效的$router
        if (!isset($router['__method']) && !isset($anyMatch['__method'])) {
            //判断是否可以补'/'
            //if($this->getMime() === 'html' && $method === 'get' && $p !== 'index' && $index + 1 === \count($this->paths)){
            //    $router = $router['index']??$router['__*']??[];
            //    if(isset($router['__method']) && (isset($router['__method'][$method]) || isset($router['__method']['any']))){
            //        Responder::redirect($this->uri.'/');
            //    }
            //}
            Error::fatal('404', 'Router not found [' . implode('/', $this->paths) . ']');
        }

        if (isset($router['__method'][$method])) {
            $this->ruler = $router['__method'][$method];
        }else {
            if (isset($router['__method']['any'])) {
                $this->ruler = $router['__method']['any'];
            }else {
                if (isset($anyMatch['__method'][$method])) {
                    $this->ruler = $anyMatch['__method'][$method];
                }elseif (isset($anyMatch['__method']['any'])) {
                    $this->ruler = $anyMatch['__method']['any'];
                }
                if ($this->ruler) {
                    $param   = array_slice($param, $startAnyMathIndex);
                    $param[] = implode('/', array_slice($this->paths, $startAnyMathIndex));
                }else {
                    Error::fatal('405', 'Method not allowed');
                }
            }
        }
        //应用Mime
        if ($this->ruler[5] && (!$this->mime || !in_array($this->mime, $this->ruler[5]))) {
            foreach ($this->ruler[5] as $mime) {
                if ($this->setMime($mime) === true) {
                    break;
                }
            }
        }
        $this->requestMethod = $method;
        $this->loadAction($this->ruler[0], $this->ruler[1]);
        $this->combParam($param, $this->ruler[2]);
    }

    /**
     * 匹配,过滤参数
     *
     * @param       $p
     * @param array $options
     */
    private function combParam(array $p, array $options)
    {
        foreach ($options as $name => $option) {
            switch ($option[0]) {
                case 'request':
                    $this->param[] = Requester::createFromRequest($option[3], $name);
                    break;
                case 'inject':
                    $class = $option[1];
                    $ruler = $option[2];
                    $param = [];
                    if (isset($ruler['param'])) {
                        $param = $ruler['param'];
                    }
                    if (isset($ruler['instance'])) {
                        $instance      = $ruler['instance'];
                        $this->param[] = $class::$instance(...$param);
                    }else {
                        $this->param[] = new $class(...$param);
                    }
                    break;
                default:
                    $this->param[] = Requester::doProcess($p[$option[4]], $option[2], 'path:' . $name);
                    break;
            }
        }
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
        if ($this->template) {
            $template = $this->template;
        }else {
            $template = $this->ruler[3];
        }
        if (strpos($template, '/') !== 0) {
            $template = '/' . $template;
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

    public function needRetry(){
        $this->isNeedRetry = true;
    }
}