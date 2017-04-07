<?php
/**
 * Auth: ax
 * Date: 2016/11/11 10:15
 */

namespace jt\utils\mind_tpl;


use jt\Exception;

class Cache
{
    /**
     * @var array
     */
    private $token       = [];
    private $sentiment   = [];
    private $source      = '';
    private $cached      = true;
    private $compileFile = '';
    private $middleFile  = '';
    private $data        = [];
    private $config      = [];
    private $context     = [];
    private $callLevel   = 0;
    private $noCacheData = [];
    const DATA_NAME = '$data';

    public function __construct($compileFile, $config, $data)
    {
        $this->compileFile = $compileFile;
        $this->config      = $config;
        $this->data        = $data;
    }

    private function pushSentiment()
    {
        if($this->source){
            $this->sentiment[] = [1, $this->source, $this->cached];
        }
        $this->source = '';
        $this->cached = true;
    }

    private function moveTokenTo($cur)
    {
        $key  = key($this->token);
        $diff = $key - $cur;
        if($diff > 0){//向前移
            for($i = 0; $i < $diff; $i++){
                prev($this->token);
            }
        }elseif($diff < 0){
            for($i = 0; $i < $diff; $i++){
                next($this->token);
            }
        }
    }

    private function cutVariable()
    {
        prev($this->token);
        list(, $item) = each($this->token);
        $name = $item[1];

        while(list(, $item) = each($this->token)){
            if(is_array($item)){
                if($item[0] === T_WHITESPACE){
                    continue;
                }elseif($item[0] === T_CONSTANT_ENCAPSED_STRING || $item[0] === T_STRING){
                    $name .= $item[1];
                }elseif($item[0] === T_VARIABLE){
                    $name .= $item[1];
                }else{
                    break;
                }
            }elseif($item === '[' || $item === ']'){
                $name .= $item;
            }else{
                break;
            }
        }
        prev($this->token);

        return $name;
    }

    private function checkValueType($name)
    {

    }

    private function parseVariableName($name)
    {
        $parsed = [];
        $ns     = explode('[', $name);

        foreach($ns as $n){
            if(substr($n, -1) === ']'){
                $n = substr($n, 0, -1);
            }
            $firstChr = substr($n, 0, 1);
            if($firstChr === '\'' || $firstChr === '"'){
                $parsed[] = [0, substr($n, 1, -1)];
            }elseif($firstChr === '$'){
                $parsed[] = [2, $n];
            }else{
                $parsed[] = [1, $n];
            }
        }

        return $parsed;
    }

    private function checkDefineVariable()
    {
        //$cur  = key($this->token);
        $name = $this->cutVariable();

        $type = 0;
        $list = [];

        list(, $item) = each($this->token);
        if($item === '='){//是在定义变量
            $type = 1;
            while(list($index, $item) = each($this->token)){
                if(is_array($item)){
                    if($item[0] === T_VARIABLE){
                        $valueName        = $this->cutVariable();
                        $ns               = $this->parseVariableName($valueName);
                        $c                = $this->isCacheData($ns);
                        $list[$valueName] = [$ns, $c, $index];
                        if(!$c){
                            $this->cached = false;
                        }
                    }elseif($item[0] === T_CLOSE_TAG){
                        break;
                    }
                }elseif($item === ';'){
                    break;
                }
            }
        }

        if(!$this->cached){
            foreach($list as $valueName => $item){
                if(!$item[1]){
                    $this->noCacheData[]      = $valueName;
                    $this->token[$item[2]][1] = '';
                }
            }
        }

        //$this->moveTokenTo($cur);

        return [$name, $type];
    }

    private function error($msg, $offset = 0, $code = 'syntaxError')
    {
        $msg .= $this->content;
        if($this->line >= 0){
            $msg .= ' In file '.$this->tpl.' line '.$this->line.' Tag pos '.($this->tagPos + $offset);
        }
        throw new Exception("$code: $msg");
    }

    private function collectVariable()
    {
        //检查是否是在定义变量
        list($defineVariable, $type) = $this->checkDefineVariable();

        if($type){
            $this->context[$this->callLevel][$defineVariable] = $type;
        }elseif($this->cached){
            $this->cached = $this->isCacheData($this->parseVariableName($defineVariable));
        }
    }

    private function collectSentiment()
    {
        while(list(, $item) = each($this->token)){
            if(is_array($item)){
                if($item[0] === T_CLOSE_TAG){
                    break;
                }
                $this->source .= $item[1];
                switch($item[0]){
                    case T_VARIABLE:
                        $this->collectVariable();
                        break;
                }
            }else{
                $this->source .= $item;
                switch($item){
                    case ';':
                    case '{':
                    case '}':
                        $this->pushSentiment();
                        break;
                }
            }
        }
        $this->pushSentiment();
        if(isset($item[0]) && $item[0] === T_CLOSE_TAG){
            $this->sentiment[] = [4, $item[1], true];
        }
        var_export($this->sentiment);
        //exit();
    }

    private function isPeriod()
    {
        return false;
    }

    private function genMiddleFilePath()
    {
        $tpl = substr($this->compileFile, strlen($this->config['runtimePath']));
        $tpl = 'cache_middle'.substr($tpl, strpos($tpl, '/', 1));

        $this->middleFile = Compile::joinPath($tpl, $this->config['runtimePath']);

        $dir = dirname($this->middleFile);
        if(!file_exists($dir)){
            mkdir($dir, 0777, true);
        }
    }

    private function printToken()
    {
        $token = $this->token;
        foreach($token as $index => &$item){
            if(is_array($item)){
                $item[0] = token_name($item[0]);
            }
        }
        var_export($token);
    }

    public function create($file)
    {
        if($this->isPeriod()){
            return;
        }
        $this->token = token_get_all(file_get_contents($this->compileFile));
        //$this->printToken();
        while(list(, $item) = each($this->token)){
            if(is_array($item)){
                switch($item[0]){
                    case T_INLINE_HTML:
                        $this->sentiment[] = [0, $item[1], []];
                        break;
                    case T_OPEN_TAG:
                    case T_OPEN_TAG_WITH_ECHO:
                        //移出去单独处理
                        $this->sentiment[] = [3, $item[1], []];
                        $this->collectSentiment();
                        break;
                }
            }
        }
        $this->writeMiddleFile();
        $this->write($file);
    }

    private function isCacheData(array $variable)
    {
        if(count($variable) === 0){
            return true;
        }

        $cache = $this->data['__cache'];
        foreach($variable as $nodes){
            foreach($nodes as $node){
                switch($node[0]){
                    case 0:
                        if(isset($cache[$node[1]])){
                            $cache = $cache[$node[1]];
                        }else{
                            return false;
                        }
                        break;
                    case 1://静态量 将值求出来
                        break;
                    case 2://变量 将值求出来
                        break;
                }
            }
        }

        return true;
    }

    public function writeMiddleFile()
    {
        $noCacheSource = [];
        $source        = '';
        $si            = 0;

        foreach($this->sentiment as $item){
            switch($item[0]){
                case 0:
                    $source .= $item[1];
                    break;
                case 1:
                    if($item[2]){
                        $source .= $item[1];
                    }else{
                        $noCacheSource[$si] = '<?php '.($item[0] == 2? 'echo ': '').$item[1].'?>';
                        $source             .= 'echo NO_CACHE_SOURCE['.$si.'];';
                        $si++;
                    }

                    break;
                case 3:
                case 4:
                    $source .= $item[1];
                    break;
            }
        }
        if($noCacheSource){
            $source = '<?php define(\'NO_CACHE_SOURCE\', '.var_export($noCacheSource, true).');?>'.PHP_EOL.$source;
        }
        $this->genMiddleFilePath();

        file_put_contents($this->middleFile, $source, LOCK_EX);
    }

    private function write($file)
    {
        $dir = dirname($file);
        if(!file_exists($dir)){
            mkdir($dir, 0777, true);
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $data = $this->data;
        ob_start();
        /** @noinspection PhpIncludeInspection */
        include($this->middleFile);
        $code = ob_get_clean();
        file_put_contents($file, $code, LOCK_EX);
    }
}