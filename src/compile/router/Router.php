<?php
/**
 * @Auth ax@csmall.com
 * @Create 2015/10/22 15:14
 */

namespace jt\compile\router;

class Router extends Action
{
    const RULER_ORDER_MAP = ['class', 'method', 'param', 'tpl', 'auth', 'mime', 'return', 'log', 'affix', 'key', 'line'];

    /**
     * 清理参数子结点数据
     *
     * @param $nodes
     * @return array
     */
    private function clearNodes($nodes)
    {
        $cleared = [];
        foreach($nodes as $item){
            $cleared[$item['name']] = $item['ruler'];
            if(isset($item['nodes'])){
                $cleared[$item['name']]['nodes'] = $this->clearNodes($item['nodes']);
            }
            $cleared[$item['name']]['_line'] = $item['line'];
            $cleared[$item['name']]['_desc'] = $item['desc'];
        }

        return $cleared;
    }

    /**
     * 清理参数
     *
     * @param array $value
     * @return array
     */
    private function clearParam(array $value)
    {
        $clean = [];
        foreach($value as $name => $item){
            $clean[$name] = [
                $item['behave'], //行为
                $item['type'], //type
                isset($item['ruler'])? $item['ruler']: [], //validate
                isset($item['nodes'])? $this->clearNodes($item['nodes']): [], //node
                isset($item['pos'])? $item['pos']: 0
            ];
        }

        return $clean;
    }

    /**
     * 清理返回类容
     *
     * @param $value
     * @return string
     */
    private function clearReturn(array $value)
    {
        $clean = $value['ruler'];
        if(isset($value['nodes']) && $value['nodes']){
            $clean['nodes'] = $this->clearNodes($value['nodes']);
        }
        $clean['_line'] = $value['line'];
        $clean['_desc'] = $value['desc'];

        return $clean;
    }

    /**
     * 对生成的Action做后期加工
     *
     * @param $action
     */
    private function modifyAction(&$action)
    {
        preg_match('/^(.*?)(?:\.(.*))?$/', $action[4], $matches);
        if($matches[1]){
            $action[4] = [$matches[1], $matches[2]??null];
        }else{
            $action[4] = [];
        }
    }

    /**
     * 获取路由规则
     *
     * @return array
     */
    private function link()
    {
        $parsedList = static::$cacheStore['action'];

        foreach($parsedList as $class => $methods){
            foreach($methods['methods'] as $ruler){
                if(!$ruler['methods']){
                    continue;
                }
                $map = &static::$parsedStore;
                foreach($ruler['path'] as $path){
                    if(!isset($map[$path])){
                        $map[$path] = [];
                    }
                    $map = &$map[$path];
                }
                foreach($ruler['methods'] as $method){
                    if(isset($map['__method'][$method])){
                        $conflictRuler = $map['__method'][$method];
                        $this->setErrorPos($this->getFileByClass($class), $ruler['line']);
                        $ef = $this->getFileByClass($conflictRuler[0]);
                        $el = $this->getLineByMethod($conflictRuler[0], $conflictRuler[1]);

                        $this->error('routerMapDuplicate',
                            "[{$conflictRuler[0]}::{$conflictRuler[1]}] 对应的路由规则与 [ {$ruler['class']} :: {$ruler['method']} ] 冲突，请检查! in {$ef} line {$el} &");
                    }else{
                        $action = [];
                        $ruler['key'] = implode('|', $ruler['methods']).':'.$ruler['uri'];
                        foreach(self::RULER_ORDER_MAP as $name){
                            $value = $ruler[$name];
                            if($name === 'param'){
                                $value = $this->clearParam($value);
                            }elseif($name === 'return' && $value){
                                $value = $this->clearReturn($value);
                            }
                            $action[] = $value;
                        }
                        $this->modifyAction($action);
                        $map['__method'][$method] = $action;
                    }
                }
            }
        }

        return static::$parsedStore;
    }

    /**
     * 生成并缓存路由
     *
     * @param string $saveAs
     * @param string $projectRoot
     * @param string $module
     *
     * @return array
     */
    public static function general($saveAs, $projectRoot, $module)
    {
        static::parse($projectRoot, $module);

        if(!static::$parseNewFile && file_exists($saveAs)){
            return include($saveAs);
        }
        if(file_exists($saveAs)){
            unlink($saveAs);
        }

        $router    = new self('', '');
        $routerMap = $router->link();

        if(!is_dir(dirname($saveAs))){
            mkdir(dirname($saveAs), 0700, true);
        }
        //file_put_contents($saveAs, "<?php\nreturn [" . static::serialize($routerMap) . '];');
        file_put_contents($saveAs, "<?php\nreturn ".var_export($routerMap, true).';', LOCK_EX);
        //>debug
        if(RUN_MODE === 'develop'){
            exec('chmod -R 777 '.RUNTIME_PATH_ROOT);
        }

        //debug<
        return $routerMap;
    }

    /**
     * 序列化路由规则 用于生成路由缓存表
     *
     * @param array $map
     *
     * @return string
     */
    private static function serialize(array $map)
    {
        $ser = '';
        self::recursionPrint($map, $ser);

        return $ser;
    }

    /**
     * 打印规则
     *
     * @param $ruler
     * @param $ser
     */
    private static function linePrint($ruler, &$ser)
    {
        foreach($ruler as $value){
            if(is_array($value)){
                $ser .= '[';
                self::linePrint($value, $ser);
                $ser .= '],';
            }else{
                $ser .= "'{$value}',";
            }
        }
        $ser = substr($ser, 0, -1);
    }

    /**
     * 打印路径
     *
     * @param $map
     * @param $ser
     * @return string
     */
    private static function recursionPrint($map, &$ser)
    {
        foreach($map as $name => $methods){
            $ser .= "'{$name}'=>[";
            if($name === '__method'){
                foreach($methods as $method => $ruler){
                    $ser .= "'{$method}'=>[";
                    self::linePrint($ruler, $ser);
                    $ser .= '],';
                }
                $ser = substr($ser, 0, -1);
            }else{
                $ser .= self::recursionPrint($methods, $ser);
            }
            $ser .= "],";
        }

        return substr($ser, 0, -1);
    }
}