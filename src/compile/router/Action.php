<?php
/**
 * User: ax@csmall.com
 * Date: 2015/5/31
 * Time: 14:04
 */

namespace jt\compile\router;

use jt\compile\Loader;
use jt\Exception;
use jt\Requester;

abstract class Action extends Loader
{
    const MODEL_ACTION = '\jt\ModelAction';

    const RULER_ORDER          = ['method', 'uri', 'tpl', 'auth', 'log', 'mime', 'affix'];//affix:doc_hidden,output_quiet
    const METHODS              = ['get', 'post', 'put', 'delete', 'head', 'options', 'any', 'cli'];//支持的动作
    const ANY_AS_METHODS       = ['get', 'post', 'put', 'delete'];//any 所代表的动作
    const REQUEST_PARAM        = [
        '\jt\Requester' => ['body', 'query', 'request', 'header'],
        '\jt\Cookie'    => ['cookie'],
        '\jt\Session'   => ['session'],
        '\jt\Responder' => ['output']
    ];
    const ENABLE_QUOTE         = ['session'];
    const VALUE_PARAM          = ['int', 'string', 'array', 'float', 'bool'];
    const HAS_CHILD_PARAM_TYPE = ['object', 'objectList', 'list'];
    const CLASS_INFO_NAMES     = ['Auth', 'Create', 'version', 'title', 'desc', 'notice'];
    const GLOBAL_BASE_PATH     = ['basePath', 'baseTplPath'];

    const METHOD_ATTRIBUTES  = [
        'methods',
        'uri',
        'class',
        'param',
        'return',
        'method',
        'path',
        'tpl',
        'mime',
        'auth',
        'log',
        'affix',
        'name',
        'desc',
        'notice',
        'line'
    ];
    const DEFAULT_INDEX      = 'index';
    const TAB_AS_SPACE_COUNT = 4;

    //默认的规则
    protected $basePath     = '/';//URI的路径前缀
    protected $baseTplPath  = '/';//TPL的路径前缀
    protected $defaultRuler = [
        'method' => 'get',
        'tpl'    => '',
        'auth'   => \Config::DEFAULT_AUTH_CHECKER,
        'log'    => \Config::DEFAULT_LOG_WRITER,
        'mime'   => '',
        'affix'  => ''
    ];

    protected $classType = 'action';//当前类的类型 区别于 model
    /**
     * 默认参数
     *
     * @var array
     */
    protected $defaultParam = [];

    /**
     * 搜集全局设置
     * 全局设置只能放在第一个注释块中
     */
    protected function collectGlobalValue()
    {
        foreach($this->commentBlocks as $key => $commentBlock){
            $this->commentLines = $commentBlock;
            unset($this->commentBlocks[$key]);
            break;
        }
        if(empty($this->commentLines)){
            return;
        }
        $ignore = $this->getValue('parseIgnore');
        if($ignore !== null && $ignore !== 'false'){
            $this->classInfo['parseIgnore'] = true;

            return;
        }
        foreach(static::GLOBAL_BASE_PATH as $name){
            $value = $this->getValue($name);
            if($value){
                if(substr($value, 0, 1) !== '/'){
                    $this->error($name.'Error', 'Action Presets ['.$name.'] 没有以 / 开始，请修正');
                }
                if(substr($value, -1, 1) !== '/'){
                    $this->error($name.'Error', 'Action Presets ['.$name.'] 没有以 / 结束，请修正');
                }
                $this->$name = $value;
            }
        }

        $this->parsed['scheme'] = 'global';
        $this->collectDocParam();
        $this->defaultParam = $this->parsed['param']??[];

        foreach($this->defaultRuler as $name => &$ov){
            $value = $this->getValue('default'.ucfirst($name));
            if($value){
                $ov = $value;
            }
        }

        foreach(static::CLASS_INFO_NAMES as $name){
            if($name === 'desc'){
                $this->classInfo[$name][] = $this->getValue($name);
                $descBuffer               = [];
                foreach($this->getCommentList(['string']) as list(, $desc)){
                    $descBuffer[] = $desc;
                }
                $last = array_pop($descBuffer);
                if($last){
                    $descBuffer[] = $last;
                }
                $this->classInfo[$name] = implode(';', $descBuffer);
            }else{
                $this->classInfo[$name] = $this->getValue($name);
            }
        }
        $this->classInfo['methods'] = [];

        $this->parsedContainer = $this->classInfo;
    }

    /**
     * 解释注解块
     * 该方法由Loader调用，一次处理一整块注释，包括name(接口名称), desc(接口描述), router(路由规则), param(参数列表), return(返回数据格式)
     */
    protected function parseComment()
    {
        $this->parseScheme();
        if($this->parsed && $this->parsed['scheme']){
            $this->parseDoc();
            $routers = $this->parseRouter();
            foreach($routers as $router){
                $this->parsed = array_merge($this->parsed, $router);
                $this->pack();
            }
        }
    }

    /**
     * 计算注释向右移的位数
     *
     * @param $line
     *
     * @return int
     */
    private function countIndent($line)
    {
        preg_match('/^([ \\t]*)/', $line, $matched);
        $spaces = str_replace("\t", static::TAB_AS_SPACE_COUNT, $matched[1]);

        return strlen($spaces);
    }

    /**
     * 将注解中的某一行送到此处来分解
     *
     * @param $line
     *
     * @return mixed
     */
    private function parseParamLine($line)
    {
        preg_match('/(\+?\w*)\:?([\w\:\\\\]*)(?: +\[([^\]]*)\])? *(.*)/', $line, $match);
        array_shift($match);
        if(strpos($match[2], '[') !== false){
            $posList = [];
            $pos     = -1;
            while($pos !== false){
                $posList[$pos] = 1;
                $pos           = strpos($match[3], '[', $pos + 1);
            }
            $pos = -1;
            while($pos !== false){
                $posList[$pos] = -1;
                $pos           = strpos($match[3], ']', $pos + 1);
            }
            unset($posList[-1]);
            $count = 1;
            foreach($posList as $pos => $d){
                $count += $d;
                if($count <= 0){
                    $match[2] .= ']'.substr($match[3], 0, $pos);
                    $match[3] = trim(substr($match[3], $pos + 1));
                    break;
                }
            }
            if($count > 0){
                $match[3] = $match[2].$match[3];
                $match[2] = '';
            }
        }
        $type = $match[1];
        if(strpos($match[0], '+') === 0){
            $list     = preg_split('/ *, */', $match[2]?: '*');
            $match[0] = substr($match[0], 1);
            if(strpos($match[1], '::') !== false){
                list($class, $method) = explode('::', $match[1]);
                $class = $this->prefixNamespace($class);
                $match[1] = $class.'::'.$method;
            }
            $match[2] = [
                'origin' => $match[1],
                'list'   => $list,
                'rule'   => 'in'
            ];
            $match[1] = '__reference';
        }else{
            $ruler = trim($type.' '.$match[2]);
            try{
                $match[2] = Requester::parseValidate($ruler, 'paramNode:'.$ruler);
            }catch(Exception $e){
                $this->throwError($e);
            }
        }
        if(!$type && isset($match[2]['type'])){
            $match[1] = $match[2]['type'];
        }
        $match[] = $this->line;

        return $match;
    }

    /**
     * 搜集解析的参数结果
     *
     * @param array $lines
     * @param int   $indent
     * @param int   $parentIndent
     * @return array
     */
    private function collectParseParam(array &$lines, $indent, &$parentIndent = null)
    {
        $parsedList = [];
        $parsed     = [];
        while(($item = array_shift($lines))){
            $line          = $item[1];
            $lineNo        = $item[2];
            $currentIndent = $this->countIndent($line);//缩进数量
            if($currentIndent !== $indent){
                array_unshift($lines, $item);
            }
            if($currentIndent > $indent){//进入下一级
                //判断是否允许降级,若不允许则报错
                if(!in_array($parsed['ruler']['type'], static::HAS_CHILD_PARAM_TYPE)){
                    $this->line = $lineNo;
                    $this->error('indentNotAllow', '不允许的缩进，上一参数类型不支持有下级元素');
                }
                $parsedList[count($parsedList) - 1]['nodes'] = $this->collectParseParam($lines, $currentIndent, $parentIndent);
                continue;
            }elseif($currentIndent < $indent){//退出到上一级
                $parentIndent = $currentIndent;
                break;
            }

            if($parentIndent){//判断对齐$parentIndent了没
                if($parentIndent < $indent){
                    $this->line = $lineNo;
                    $this->error('paramIndentNotAlignStart', '此处缩进未能对齐开始处');
                }elseif($parentIndent > $indent){
                    break;
                }
            }

            $parentIndent = null;
            $p            = $this->parseParamLine(trim($line));
            $parsed       = [
                'name'  => $p[0],
                'type'  => $p[1],
                'ruler' => $p[2],
                'desc'  => $p[3],
                'line'  => $p[4]
            ];
            $parsedList[] = $parsed;
        }

        return $parsedList;
    }

    /**
     * 解析参数
     *
     * @param array $lines 多行参数
     *
     * @return array
     */
    private function parseParam($lines)
    {
        if(!$lines){
            return [];
        }
        $indent = $this->countIndent($lines[0][1]);//缩进数量
        $params = $this->collectParseParam($lines, $indent);

        return $params;
    }

    /**
     * 解析在参数中捕获到的参数验证规则
     *
     * @param string $ruler
     * @param string $type 变量类型
     *
     * @return mixed
     */
    private function parseParamRuler($ruler, $type)
    {
        if(in_array($type, Requester::CONVERT_TYPE) || in_array($type, Requester::SINGLE_TYPE) || in_array($type, Requester::MULTI_TYPE)){
            $ruler = $type.' '.$ruler;
        }elseif(isset(self::REQUEST_PARAM[$type])){
            $ruler = 'type:object '.$ruler;
        }else{
            $ruler = 'type:'.$type.' '.$ruler;
        }

        return trim($ruler);
    }

    /**
     * 解析暴露的Model的说明文档
     */
    private function parseModelDoc()
    {
        //获取说明
        $this->parsed['return'] = [];
        $this->parsed['name']   = '';
        $this->parsed['desc']   = '';
        $this->parsed['notice'] = '';
    }

    private function findParamType($name)
    {
        foreach(self::REQUEST_PARAM as $type => $item){
            if(in_array($name, $item)){
                return $type;
            }
        }

        return null;
    }

    /**
     * 搜集注解中的参数信息
     */
    private function collectDocParam()
    {
        reset($this->commentLines);
        while(($param = $this->getValue('param', false))){
            preg_match('/([\w\\\\]*) *\$([a-z]\w*)(?: +\[([^\]]*)\])? *(.*)/', $param, $match);
            array_shift($match);
            $type = $this->prefixNamespace($match[0]);
            $name = $match[1];
            if(!isset($this->parsed['param'][$name])){//忽略在方法参数中不存在的参数
                if($this->parsed['scheme'] === 'router'){
                    $this->error('docParamNotInMethod',
                        '注解中的参数 ['.$name.'] 在方法 ['.$this->parsed['class'].'::'.$this->parsed['method'].'] 中不存在');
                    continue;
                }else{
                    $this->parsed['param'][$name] = [
                        'type' => null
                    ];
                }
            }
            $mp   = &$this->parsed['param'][$name];
            $type = $type?: $mp['type'];

            if($type && !$mp['type']){
                $mp['type'] = $type;
                $mp['line'] = $this->line; //标明此处的类型定义来自注解
            }elseif($type !== $mp['type']){
                $this->error('paramTypeDiscord', '参数类型不一致。['.$type.']的参数类型['.$name.'] 与方法声明中的类型['.$mp[0].'] 不一致');
            }
            $ruler = $this->parseParamRuler($match[2], $type);
            $desc  = $match[3];
            $nodes = [];

            $paramType = $this->findParamType($name);
            if($paramType){
                if($type && $type !== $paramType){
                    $this->error('commentParamTypeIll', '注释中参数类型错误，期待 ['.$paramType.'],此处为 ['.$type.']');
                }
                $lines = $this->getCommentList(['string']);
                $nodes = $this->parseParam($lines);
            }elseif(isset(self::REQUEST_PARAM[$type])){
                $this->error('commentParamTypeIll', '注释中参数名错误，期待为 ['.implode(', ', self::REQUEST_PARAM[$type]).'] 之一,此处为 ['.$name.']');
            }
            $mp['ruler'] = $ruler;
            $mp['nodes'] = $nodes;
            $mp['desc']  = $desc;
        }
    }

    /**
     * 搜集注解中的返回值信息
     */
    private function collectDocReturn()
    {
        $return = $this->getValue('return');
        if($return === null){
            $this->parsed['return'] = [];

            return;
        }
        preg_match('/([^ ]*) *(?:\[(.*)\])? *(.*)/', $return, $match);
        array_shift($match);
        $parsed = [
            'name' => '',
            'desc' => $match[2],
            'line' => $this->line
        ];
        $ruler  = $match[0] === 'array'? '': $match[0].' ';
        $ruler  .= $match[1];
        try{
            $parsed['ruler'] = Requester::parseValidate($ruler, 'return:'.$ruler);
        }catch(Exception $e){
            if($this->parsed['scheme'] === 'router'){
                $this->throwError($e);
            }else{
                return;
            }
        }

        if($parsed['ruler']['type'] === 'string'){
            $parsed['ruler']['type'] = 'object';
        }

        $lines = $this->getCommentList(['string']);

        $type = $parsed['ruler']['type'];
        if(in_array($type, Requester::MULTI_TYPE)){
            $parsed['nodes'] = $this->parseParam($lines);
        }

        $this->applyPage($parsed);

        $this->parsed['return'] = $parsed;
    }

    private function fillPageItem($name, $desc, $line)
    {
        return [
            'name'  => $name,
            'desc'  => $desc,
            'line'  => $line,
            'ruler' => [
                'rule'   => 'int',
                'type'   => 'int',
                'format' => null,
                'min'    => 0,
                'max'    => 0
            ]
        ];
    }

    /**
     * 快速返回分页格式
     *
     * @param array $parsed 解析结果
     */
    private function applyPage(&$parsed)
    {
        if(isset($parsed['ruler']['page'])){
            $parsed['ruler']['rule'] = preg_replace('/(?:^| )page(?: |$)/', ' ', $parsed['ruler']['rule']);
            $parsed['nodes']         = [
                [
                    'name'  => 'list',
                    'desc'  => '列表',
                    'line'  => $parsed['line'],
                    'ruler' => $parsed['ruler'],
                    'nodes' => $parsed['nodes']
                ],
                $this->fillPageItem('size', '每页记录数', $parsed['line']),
                $this->fillPageItem('page', '当前页数', $parsed['line']),
                $this->fillPageItem('total', '总记录数', $parsed['line'])
            ];
            $parsed['ruler']['type'] = 'object';
            $nodes                   = &$parsed['nodes'][0]['nodes'];
        }else{
            $nodes = &$parsed['nodes'];
        }
        if(is_array($nodes)){
            foreach($nodes as &$node){
                $this->applyPage($node);
            }
        }
    }

    private function parseInfo($type){
        $buffer           = [];
        $info = $this->getValue($type);
        if($info){
            $buffer[] = $info;
            foreach($this->getCommentList(['string']) as list(, $info)){
                $buffer[] = $info;
            }
            $lastLine = array_pop($buffer);
            if($lastLine){
                $buffer[] = $lastLine;
            }
            $this->parsed[$type]   = implode('; ', $buffer);
        }
    }

    /**
     * 解析带路由的方法的说明文档
     */
    private function parseRouterDoc()
    {
        //获取说明
        $this->parsed['name'] = $this->getValue('string');

        $this->parseInfo('desc');
        $this->parseInfo('notice');

        $this->collectDocParam();
        $this->collectDocReturn();
    }

    /**
     * 解析注解中用来生成说明文档的内容
     */
    private function parseDoc()
    {
        switch($this->parsed['scheme']){
            case 'model':
                $this->parseModelDoc();
                break;
            case 'method':
            case 'router':
                $this->parseRouterDoc();
                break;
        }
    }

    /**
     * 解析注解中的路由规则
     */
    private function parseScheme()
    {
        $this->parsed = [
            'line'   => $this->line,
            'scheme' => '',
            'param'  => []
        ];

        $scheme = $this->collect([
            T_USE,
            T_PUBLIC,
            T_PRIVATE,
            T_PROTECTED
        ], [T_WHITESPACE], ['useful']);

        $type  = $this->collect([T_FUNCTION], [T_WHITESPACE]);
        $model = $this->getValue('model');

        if($type === 'function'){
            if($scheme === ''){
                $scheme = 'public';
            }
        }elseif($model === '' && $scheme === 'use'){
            $model = $this->collect([T_STRING, T_NS_SEPARATOR], [T_WHITESPACE]);
        }else{
            return;
        }

        if($model){
            if(strpos($model, '\\') !== 0){
                $model = '\\'.$model;
            }
            $this->parsed['scheme'] = 'model';
            $this->parsed['model']  = $model;
            $this->parsed['access'] = $this->getValue('access');
        }elseif($scheme === 'public'){
            $this->parsed['scheme'] = 'router';
            $this->collectMethodParam();
        }elseif($scheme === 'private' || $scheme === 'protected'){
            $this->parsed['scheme'] = 'method';
            $this->collectMethodParam();
        }
    }

    private function parseRouter()
    {
        $res     = [];
        $routers = $this->getCommentList(['router'], ['all'], [], true);
        foreach($routers as $router){
            $this->parsed['line'] = $router[2];
            $this->line           = $router[2];
            $item                 = $this->parseRouterRuler($router[1]);
            if($this->parsed['scheme'] === 'method'){
                $this->error('VisibleNotAllow', '该方法不存在或可见性不为 public');
            }
            $res[] = $item;
        }
        if(!$res){
            $res[] = [
                'scheme'  => 'method',
                'uri'     => '',
                'methods' => [],
            ];
        }

        return $res;
    }

    /**
     * 被充完整命名空间
     *
     * @param $name
     *
     * @return string
     */
    private function prefixNamespace($name)
    {
        if(!$name){
            return '';
        }
        if(in_array($name, static::VALUE_PARAM)){
            return $name;
        }
        if(strpos($name, '\\') === 0){
            return $name;
        }

        if(array_key_exists($name, $this->useList)){
            return $this->useList[$name];
        }

        return '\\'.$this->namespace.'\\'.$name;
    }

    /**
     * 搜集方法中的参数 eg:public function method($param)中的 $param
     */
    private function collectMethodParam()
    {
        $method = $this->collect([T_STRING], [T_WHITESPACE, T_FUNCTION]);
        if(!$method){//不是方法上的注释
            return;
        }
        $this->parsed['class']  = $this->class;
        $this->parsed['method'] = $method;

        $this->collect(['('], [T_WHITESPACE], ['(']);
        $params = [];
        do{
            $type      = $this->collect([T_STRING, T_NS_SEPARATOR, T_ARRAY], [T_WHITESPACE, ','], [T_VARIABLE]);
            $reference = $this->collect(['&'], [T_WHITESPACE]);
            $name      = substr($this->collect([T_VARIABLE], [T_WHITESPACE], [T_VARIABLE]), 1);
            //if($reference && !in_array($name, self::ENABLE_QUOTE)){
            //    $this->error('paramNotAllowByReference', 'Action ['.$this->class.'] 中的参数 ['.$name.'] 不允许通过引用传值，请检查');
            //}
            if($name){
                $default       = $this->collect([T_STRING], [T_WHITESPACE, '=']);
                $params[$name] = [
                    'type'    => $this->prefixNamespace($type),
                    'default' => $default,
                    'line'    => $this->line
                ];
            }
        }while($name);
        $this->parsed['param'] = $params;
    }

    /**
     * 解析路由规则中每一项的值
     *
     * @param $ruler
     * @return array
     */
    private function parseRouterRuler($ruler)
    {
        $res = [];
        $as  = preg_split('/ +/', $ruler);
        foreach($as as $index => $a){
            if(strpos($a, ':')){
                list($type, $name) = \explode(':', $a, 2);
                if(in_array($type, self::RULER_ORDER)){
                    $res[$type] = $name;
                }elseif($index === 1){
                    $res['uri'] = $a;
                }else{
                    $this->error('routerRulerNameError', 'Action ['.$this->class.'] 中的规则 ['.$ruler.'] 的规则名 ['.$type.'] 不正确，请检查');
                }
            }else{
                if($a && count(self::RULER_ORDER) > $index){
                    $res[self::RULER_ORDER[$index]] = $a;
                }else{
                    $this->error('routerRulerOverflow', 'Action ['.$this->class.'] 中的规则 ['.$ruler.'] 数量太多，请检查');
                }
            }
        }

        if($res['uri'] === './'){
            $res['uri'] = '';
        }

        if(isset($res['method'])){
            $methods = explode(',', $res['method']);
            unset($res['method']);
        }else{
            $methods = ['get'];
        }
        foreach($methods as $method){
            if(!in_array($method, static::METHODS)){
                $this->error('routerMethodIll', 'Action ['.$this->class.'] 中的规则 ['.$ruler.'] 的method值 ['.$method.'] 错误，请检查');
            }
        }

        $res['methods'] = $methods;

        return $res;
    }

    /**
     * 将全局值应用到此处来
     *
     * @param $parsed
     */
    protected function applyDefaultValue(&$parsed)
    {
        foreach(static::RULER_ORDER as $name){
            if(!isset($parsed[$name])){
                $parsed[$name] = $this->defaultRuler[$name]??'';
            }
        }
        //为路径加上前缀
        if(substr($parsed['uri'], 0, 1) !== '/'){
            $parsed['uri'] = $this->basePath.$parsed['uri'];
        }
        //为路径加上index
        if(substr($parsed['uri'], -1, 1) === '/'){
            $parsed['uri'] .= static::DEFAULT_INDEX;
        }
        //加上模板前缀
        if(substr($parsed['tpl'], 0, 1) !== '/'){
            $parsed['tpl'] = $this->baseTplPath.$parsed['tpl'];
        }
        //自动设定模板文件
        if(substr($parsed['tpl'], -1, 1) === '/'){
            $parsed['tpl'] .= substr($parsed['uri'], 1);
            $lastSlashPos  = strrpos($parsed['tpl'], '/') + 1;
            if(substr($parsed['tpl'], $lastSlashPos, 1) === '*'){
                $parsed['tpl'] = substr($parsed['tpl'], 0, $lastSlashPos).static::DEFAULT_INDEX;
            }
        }
    }

    /**
     * 搜集路径中的参数
     *
     * @param $uri
     *
     * @return array
     */
    protected function collectPathParam($uri)
    {
        $uris = \explode('/', $uri);
        array_shift($uris);
        $paths     = [];
        $pathParam = [];

        foreach($uris as $u){
            if(strpos($u, ':') !== false){//是一个变量
                list($type, $name) = \explode(':', $u, 2);
                $pathParam[$name] = $type;
                $u                = '__var';
            }elseif(strpos($u, '*') === 0){
                $pathParam[substr($u, 1)] = '';
                $u                        = '__*';
            }
            $paths[] = $u;
        }

        return [$paths, $pathParam];
    }

    /**
     * 检查获得的参数
     *
     * @param array $attr
     *
     * @return array
     */
    protected function checkParsedValue(array $attr)
    {
        if(strpos($attr['auth'], '.') === 0){
            $attr['auth'] = $this->defaultRuler['auth'].$attr['auth'];
        }
        if($attr['auth'] !== 'public' && strpos($attr['auth'], '\\') !== 0){
            $attr['auth'] = self::$namespaceRoot.'\\auth\\'.$attr['auth'];
        }

        if($attr['log'] === 'none'){
            $attr['log'] = '\jt\lib\log\DarkHole';
        }

        if(strpos($attr['log'], '\\') !== 0){
            $attr['log'] = self::$namespaceRoot.'\\log\\'.$attr['log'];
        }

        if($attr['methods'] === ['cli'] && strpos($attr['affix'], 'output_') === false){
            $attr['affix'] .= 'output_quiet';
        }

        $attr['mime'] = $attr['mime']? preg_split('/ *, */', $attr['mime']): [];

        return $attr;
    }

    /**
     * 整理解析结果
     *
     * @return array
     */
    protected function packParsed()
    {
        $parsed = $this->parsed;
        $action = $this->class.'::'.$this->method;

        $this->applyDefaultValue($parsed);
        //生成参数
        $this->line = $parsed['line'];
        list($paths, $pathParam) = $this->collectPathParam($parsed['uri']);
        //分离Path参数和Request参数
        $param = [];
        //对比参数类型

        $processPathParamCount = 0;
        $pathParamKeys         = array_keys($pathParam);
        foreach($pathParamKeys as $key){
            if(!isset($parsed['param'][$key]) && isset($this->defaultParam[$key])){
                $parsed['param'][$key] = $this->defaultParam[$key];
            }
        }

        foreach($parsed['param'] as $name => $item){
            $this->line = $item['line'];
            $type       = $item['type'];
            $paramType  = $this->findParamType($name);
            if($paramType){
                if($type && $paramType !== $type){
                    $this->error('paramTypeIll', '参数 ['.$name.'] 的类型此处为 ['.$type.'], 应该为 ['.$paramType.']');
                }
                $item['behave'] = $paramType;
            }elseif($type && !in_array($type, static::VALUE_PARAM)){
                $item['behave'] = 'inject';
            }elseif(isset($pathParam[$name])){
                if($type && $pathParam[$name] && $pathParam[$name] !== $type){
                    $this->error('pathParamTypeIll', '路径中的参数 ['.$name.'] 的类型与规则中声明的不一致');
                }
                if(!$type){
                    $item['type'] = $pathParam[$name]?: 'string';
                }
                $item['ruler']  = trim(isset($item['ruler'])? $item['ruler']: ''.' '.$item['type']);
                $item['behave'] = 'value';
                $item['pos']    = array_search($name, $pathParamKeys);//在参数中出现的顺序，便于后序生成参数
                $processPathParamCount++;
            }elseif($this->parsed['scheme'] !== 'method'){
                $this->error('routerMapNameError', $action.' 对应的参数名 ['.$name.'] 不一致，请检查');
            }
            if(isset($item['ruler']) && $item['ruler']){
                try{
                    $item['ruler'] = Requester::parseValidate($item['ruler'], 'param['.$name.']:'.$item['ruler']);
                }catch(Exception $e){
                    $this->throwError($e);
                }

            }else{
                $item['ruler'] = [];
            }
            $param[$name] = $item;
        }

        if(count($pathParam) < $processPathParamCount){//参数数量不一致
            $this->error('routerMapCountError', $action.'路径中的参数不足，请检查');
        }

        $parsed['path']  = $paths;
        $parsed['param'] = $param;

        $res = [];
        foreach(self::METHOD_ATTRIBUTES as $name){
            $res[$name] = isset($parsed[$name])? $parsed[$name]: '';
        }

        return $this->checkParsedValue($res);
    }

    /**
     * 解析控制Model的访问范围
     *
     * @param $access
     *
     * @return array
     */
    private function parseAccess($access)
    {
        $as    = preg_split('/ *, */', $access);
        $ruler = [];
        foreach($as as $v){
            $vs            = explode(':', $v);
            $ruler[$vs[0]] = $vs[1];
        }

        return $ruler;
    }

    /**
     * 构建Model的参数
     *
     * @param string $type
     *
     * @return array
     */
    private function buildModelParam($type = null)
    {
        return [
            'type'  => $type?: array_keys(self::REQUEST_PARAM)[0],
            'model' => $this->parsed['model'],
            'ruler' => '',
            'line'  => $this->line
        ];
    }

    /**
     * 为暴露的Model构建访问路由
     *
     * @param $method
     * @param $list
     *
     * @return array
     */
    private function buildModelRouter($method, $list)
    {
        $router = array_merge($this->parsed, [
            'class'   => static::MODEL_ACTION,
            'scheme'  => 'router',
            'param'   => [
                'model' => $this->buildModelParam($this->parsed['model'])
            ],
            'methods' => [$method],
            'uri'     => $this->parsed['uri'].($list? 'list': '')
        ]);

        if($list){
            if($method === 'post' || $method === 'put'){
                $router['param']['body'] = $this->buildModelParam();
            }else{
                $router['param']['query'] = $this->buildModelParam();
            }
        }else{
            if($method !== 'post'){
                $router['uri']         .= ':id';
                $router['param']['id'] = $this->buildModelParam('string');
            }
            if($method === 'post' || $method === 'put'){
                $router['param']['body'] = $this->buildModelParam();
            }
        }

        if(isset($access[$method.$list])){
            $router['auth'] = $access[$method.$list];
        }
        $router['method'] = $method.$list;

        return $router;
    }

    /**
     * 处理注解中暴露到前端的Model
     *
     * @return array
     */
    private function expansionModel()
    {
        $access = $this->parseAccess($this->parsed['access']);
        unset($this->parsed['access']);
        $methods = $this->parsed['methods'];

        if(substr($this->parsed['uri'], 0, -1) !== '/'){
            $this->parsed['uri'] .= '/';
        }

        $parsedPatch = [];

        foreach($methods as $m){
            if(!isset($access[$m]) || $access[$m] !== 'block'){
                $parsedPatch[$m] = $this->buildModelRouter($m, '');
            }
            if(!isset($access[$m.'List']) || $access[$m.'List'] !== 'block'){
                $parsedPatch[$m.'List'] = $this->buildModelRouter($m, 'List');
            }
        }

        return $parsedPatch;
    }

    /**
     * 整理打包路由的解析结果
     */
    protected function pack()
    {
        if($this->parsed['scheme'] === 'model'){
            $ps = $this->expansionModel();
            foreach($ps as $method => $parsed){
                $this->method = $method;
                $this->parsed = $parsed;
                $this->pack();
            }
        }else{
            $this->parsedContainer['methods'][] = $this->packParsed();
        }
    }
}
