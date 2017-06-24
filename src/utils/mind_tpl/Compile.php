<?php
/**
 * Auth: ax
 * Date: 2016/10/31 17:18
 */

namespace jt\utils\mind_tpl;

use jt\Exception;

class Compile
{
    /**
     * 传递数据的容器名称
     */
    const DATA_NAME = '$data';
    /**
     * @var array 保存依赖关系
     */
    protected static $dependency = [];
    /**
     * @var array 支持的标签类型和符号
     */
    protected static $labels = [
        'if'          => 'if',
        'elseif'      => 'elseif',
        'else'        => 'else',
        'ifEnd'       => '/if',
        'foreach'     => 'foreach',
        'foreachEnd'  => '/foreach',
        'block'       => 'block ',
        'blockEnd'    => '/block',
        'for'         => 'for',
        'forEnd'      => '/for',
        'while'       => 'while',
        'whileEnd'    => '/while',
        'do'          => 'do',
        'doEnd'       => '/do',
        'let'         => 'let',
        'const'       => 'const',
        'literal'     => 'literal',
        'include'     => 'include ',
        'extends'     => 'extends ',
        'function'    => 'function',
        'functionEnd' => '/function',
        'void'        => 'void ',
        'php'         => 'php',
        'phpEnd'      => '/php'
    ];
    /**
     * @var array 支持的运算符和运算符格化式的输出
     */
    protected static $symbols = [
        '&&'   => ' && ',
        '?'    => '?',
        ':'    => ':',
        '!'    => '!',
        '==='  => ' === ',
        '=='   => ' == ',
        '+='   => ' += ',
        '-='   => ' -= ',
        '++'   => '++ ',
        '--'   => '-- ',
        '=>'   => ' => ',
        '>='   => ' >= ',
        '<='   => ' <= ',
        '+'    => ' + ',
        '='    => ' = ',
        '-'    => ' - ',
        '*'    => ' * ',
        '/'    => ' / ',
        '>'    => ' > ',
        '<'    => ' < ',
        '%'    => ' % ',
        '&'    => ' & ',
        '. '   => ' . ',
        ','    => ', ',
        ';'    => '; ',
        ' as ' => ' as '
    ];
    /**
     * @var array 允许出现在extends标签前的标签
     */
    protected static $allowAtExtendsBefore = ['literal', 'let', 'const'];

    /**
     * @var string 模板路径
     */
    protected $tpl = '';
    /**
     * @var array 当前配置
     */
    protected $config = [
        'leftDelimiter'  => '{{',
        'rightDelimiter' => '}}'
    ];
    /**
     * @var resource 打开的模板文件
     */
    protected $fh = null;
    /**
     * @var array 包函、继承栈，避免循环引用
     */
    protected $chain = [
        'include' => [],
        'extends' => []
    ];
    /**
     * @var array 开始标签
     */
    protected $startLabel = [];
    /**
     * @var array 解析结果，将基于此内容生成编译结果文件
     */
    protected $parsedStream = [];
    /**
     * @var string 当前正在处理的模板文件中的某一行的内容
     */
    protected $content = '';
    /**
     * @var int 当前正在处理模板文件的行号，便于错误定位
     */
    protected $line = -1;
    /**
     * @var int 标签的开始位置，便于错误定位
     */
    protected $tagPos = 0;
    /**
     * @var int 当前处理的内容的偏移量
     */
    protected $offset = 0;
    /**
     * @var array 搜集在模板中定义的变量
     */
    protected $context = [];
    /**
     * @var int 块嵌套深度，用来限定模板中定义的变量的作用域
     */
    protected $callLevel = 0;//调用的深度
    /**
     * @var array 将表达式中用"'"包裹的内容提取出来临时存放在此处，以免干扰解析
     */
    protected $quoteList = [];
    /**
     * @var array 标签的嵌套栈，用来排除错误的标签配对
     */
    private $tagStack = [];
    /**
     * @var array do标签中将条件前置了，用来存放前置的条件
     */
    private $doStack = [];
    /**
     * @var array 存放block标签
     */
    private $blockStack = [];
    /**
     * @var array 定位块所在的位置
     */
    private $blockAddress = [];
    /**
     * @var array 应忽略的标签
     */
    private $ignoreTag = ['extends'];

    /**
     * Compile constructor.
     *
     * @param string $tpl 模板路径
     * @param array  $config 配置
     */
    public function __construct(string $tpl, array $config)
    {
        $this->tpl    = self::joinPath($tpl);
        $this->config = array_replace_recursive($this->config, $config);
    }

    /**
     * 环境初化工作
     */
    protected function initEvn()
    {
        $this->startLabel = [$this->config['leftDelimiter'].'*', $this->config['leftDelimiter']];
    }

    /**
     * 加载缓存的依赖表，根所此表确定依赖的文件是否要重新解析
     */
    protected function loadDependency()
    {
        $file = $this->config['runtimePath'].'/dependency.php';
        if (file_exists($file)) {
            /** @noinspection PhpIncludeInspection */
            self::$dependency = include($file);
        }else {
            self::$dependency[$this->tpl] = [
                'createTime' => 0,
                'list'       => []
            ];
        }
    }

    protected function saveDependency()
    {
        $file = $this->config['runtimePath'].'/dependency.php';

        self::$dependency[$this->tpl] = [
            'creteTime'    => filectime($this->tpl),
            'chain'        => $this->chain,
            'blockAddress' => $this->blockAddress
        ];

        file_put_contents($file, "<?php return ".var_export(self::$dependency, true).';', LOCK_EX);
    }

    /**
     * 判断依赖的中间文件是否过期
     *
     * @return bool
     */
    protected function isPeriod()
    {
        if (empty(self::$dependency)) {
            $this->loadDependency();
        }
        //进一步检查文件是否有改动
        if (isset(self::$dependency[$this->tpl])) {
            if (self::$dependency[$this->tpl]['creteTime'] === filectime($this->tpl)) {
                /** @noinspection PhpIncludeInspection */
                $this->parsedStream = include($this->genParsedFile($this->tpl));
                $this->chain        = self::$dependency[$this->tpl]['chain'];
                $this->blockAddress = self::$dependency[$this->tpl]['blockAddress'];

                return true;
            }
        }

        return false;
    }

    /**
     * 输出解析中的错误的信息
     *
     * @param        $msg
     * @param int    $offset 错误位置的偏移量
     * @param string $code
     * @throws Exception
     */
    protected function error($msg, $offset = 0, $code = 'syntaxError')
    {
        $msg .= $this->content;
        if ($this->line >= 0) {
            $msg .= ' In file '.$this->tpl.' line '.$this->line.' Tag pos '.($this->tagPos + $offset);
        }
        throw new Exception("$code: $msg");
    }

    /**
     * 打开模板文件
     *
     * @return resource
     */
    private function openSourceFile()
    {
        if (!file_exists($this->tpl)) {
            $this->error($this->tpl.' file not exists!', 0, 'tplNotExists');
        }
        $this->line   = 0;
        $this->tagPos = 0;

        return fopen($this->tpl, 'r');
    }

    /**
     * 定位块存放的位置，以便后续处理
     *
     * @return array
     */
    protected function positionBlock()
    {
        $address = [];
        foreach ($this->blockStack as $block) {
            $address[] = count($block);
        }
        $address[] = count($this->parsedStream);

        return array_reverse($address);
    }

    /**
     * 保存一条解析的语句
     *
     * @param string $tag 标签类型
     * @param string $content 解析的结果
     */
    protected function pushParsedStream($tag, $content)
    {
        if ($tag === 'block') {
            $this->blockAddress[$content][] = $this->positionBlock();
            array_unshift($this->blockStack, []);

            return;
        }elseif ($tag === 'blockEnd') {
            $tag     = 'block';
            $content = array_shift($this->blockStack);
        }

        if (count($this->blockStack)) {
            $this->blockStack[0][] = [$tag, $content, $this->line, $this->tagPos];
        }else {
            $this->parsedStream[] = [$tag, $content, $this->line, $this->tagPos];
        }
    }

    /**
     * 计算单行标签内容的长度
     *
     * @return int
     */
    protected function contentLength()
    {
        $endPos = strpos($this->content, $this->config['rightDelimiter'], $this->offset);
        if ($endPos === false) {
            $this->error('标签未关闭');
        }
        $length       = $endPos - $this->offset;
        $this->offset = $endPos + strlen($this->config['rightDelimiter']);

        return $length;
    }

    /**
     * 基础表达式处理，支持点号访问
     *
     * @param string $content
     * @param string $tag
     * @param bool   $define
     * @return string
     */
    protected function parseSimpleExpress(&$content, $tag = '', $define = false)
    {
        //运算符
        foreach (self::$symbols as $symbol => $literal) {
            if (strpos($content, $symbol) !== false) {
                $cs = explode($symbol, $content, 2);
                foreach($cs as &$c){
                    $c = trim($c);
                }
                $this->parseSimpleExpress($cs[1], $tag, $define || $symbol === '=>' || $symbol === ' as ');
                $this->parseSimpleExpress($cs[0], $tag, $define || $symbol === '=');
                $content = implode($literal, $cs);
                break;
            }
        }

        if (preg_match('/^\$[a-zA-Z]+\w*(?:\.\$?\w+)*$/', $content)) {
            $content = $this->parsedVariable($content, $define);
        }

        return $content;
    }

    /**
     * 解析默认值表达式
     *
     * @param $content
     */
    protected function parseDefaultValue(&$content)
    {
        list($type, $pos) = $this->adjustTagType($content, 0, ['||' => '||']);
        if ($type === '||') {
            $s = substr($content, 0, $pos);
            $e = substr($content, $pos + 2);
            $this->parseDefaultValue($e);

            if ($s && preg_match('/^\$[a-zA-Z]+\w*(?:\.\$?\w+)*$/', $s)) {
                $content = '(isset('.$s.') && '.$s.')?'.$s.':'.$e;
            }else {
                $content = $s.'?'.$s.':'.$e;
            }
        }
    }

    /**
     * 解析使用到的过滤器
     *
     * @param $content
     */
    protected function parseFilter(&$content)
    {
        $cs = explode('|', $content);
        $s  = array_shift($cs);
        foreach ($cs as $e) {
            $sp = explode(':', $e);
            $fn = array_shift($sp);
            foreach ($sp as &$p) {
                if (substr($p, 0, 1) !== '\'' && !is_numeric($p) && substr($p, 0, 1) !== '$' && !$this->isLocalVariable($p)) {
                    $p = '\''.$p.'\'';
                }
            }
            array_unshift($sp, $s);
            $param = '('.implode(',', $sp).')';
            $s     = '$'.$fn.$param;
        }
        $content = $s;
    }

    /**
     * 函数调用  匿名函数的调用，全部转成  call_user_func_array
     *
     * @param string $content 待解析的内容
     * @param string $tag
     */
    protected function parseCall(&$content, $tag)
    {
        $s             = 0;
        $express       = '';
        $callStack     = [];
        $name          = '';
        $contentLength = strlen($content);
        if ($tag === 'let' || $tag === 'const') {
            list($name, $content) = explode('=', $content, 2);
        }
        for ($i = 0; $i < $contentLength; $i++) {
            if ($content[$i] === '(') {
                $fn = substr($content, $s, $i - $s);
                $fn = $this->parsedVariable($fn, $tag === 'function');
                if (substr($fn, 0, 1) === '$') {
                    $pos = strrpos($fn, '[');
                    if ($pos) {
                        $fn = '['.substr($fn, 0, $pos).', '.substr($fn, $pos + 1, -1).']';
                    }
                    $callStack[] = [1, $i];
                    $express     .= 'call_user_func_array('.$fn.', [';
                }else {
                    $callStack[] = [0, $i];
                    $express     .= $fn.'(';
                }
                $s = $i + 1;
            }elseif ($content[$i] === ')') {
                $param = substr($content, $s, $i - $s);
                $ps    = explode(',', $param);

                foreach ($ps as &$p) {
                    $this->parseSimpleExpress($p, $tag, $tag === 'function');
                }
                if (!$callStack) {
                    $this->error('错误的关闭括号。', $i + 2);
                }
                $sign = array_pop($callStack);
                if ($sign[0]) {
                    $express .= implode(', ', $ps).'])';
                }else {
                    $express .= implode(', ', $ps).')';
                }

                $s = $i + 1;
            }
        }
        if ($callStack) {
            $sign = array_pop($callStack);
            $this->error('有括号未关闭。', $sign[1] + 2);
        }

        if ($express) {
            $suffix = '';
            if ($s < $contentLength) {
                $suffix = substr($content, $s);
                $this->parseSimpleExpress($suffix, $tag);
            }
            $content = $express.$suffix;
        }else {
            $this->parseSimpleExpress($content, $tag);
        }

        if ($tag === 'let' || $tag === 'const') {
            $content = $this->parsedVariable($name, true).' = '.$express;
        }
    }

    /**
     * 解析表达式
     *
     * @param string $content
     * @param string $tag
     */
    protected function parseExpress(&$content, $tag)
    {
        $this->extractQuote($content);
        $this->parseDefaultValue($content);
        $this->parseFilter($content);
        $this->parseCall($content, $tag);
        $this->fillQuote($content);
    }

    /**
     * 判断是否是本地变量
     *
     * @param $name
     * @return bool
     */
    protected function isLocalVariable($name)
    {
        for ($i = $this->callLevel; $i >= 0; $i--) {
            if (isset($this->context[$i])) {
                $ns = explode('.', $name);
                do {
                    $n = implode('.', $ns);
                    if (isset($this->context[$i][$n])) {
                        return true;
                    }
                    array_pop($ns);
                }while ($ns);
            }
        }

        return false;
    }

    /**
     * 为变量加上合适的前缀
     *
     * @param $name
     * @return string
     */
    protected function fixVariable($name)
    {
        $name = trim($name);
        if ($name !== self::DATA_NAME && substr($name, 0, 6) !== self::DATA_NAME.'.' && substr($name, 0,
                1) === '$' && !$this->isLocalVariable($name)
        ) {
            $name = self::DATA_NAME.'.'.substr($name, 1);
        }

        return $name;
    }

    /**
     * 检查是否发生了循环引用
     *
     * @param string $file
     */
    protected function checkCircularReference($file)
    {
        $check = function ($f) use (&$check, $file){
            if (isset(self::$dependency[$f]['chain'])) {
                foreach (self::$dependency[$f]['chain'] as $tag => $chain) {
                    foreach ($chain as $f => $null) {
                        if ($f === $file) {
                            $this->tpl = $f;
                            $this->error('Circular reference: ');
                        }
                        $check($f);
                    }
                }
            }
        };
        $check($file);
    }

    /**
     * 检查变量名的合法性
     *
     * @param $name
     * @param $mustVariable
     */
    protected function checkVariable($name, $mustVariable)
    {
        if ($mustVariable) {
            if (substr($name, 0, 1) !== '$') {
                $this->error('变量名['.$name.']需以"$"为前缀.');
            }
        }
        if ($name === '$') {
            $this->error('变量名不能为空');
        }
    }

    /**
     * 解析变量
     *
     * @param string $name 变量名
     * @param bool   $isDefine 是否是在定义一个变量
     * @param bool   $mustVariable 是否必须是一个变量
     * @return string
     */
    private function parsedVariable($name, $isDefine = false, $mustVariable = false)
    {
        $name = trim($name);
        if (!$name) {
            return $name;
        }
        $this->checkVariable($name, $mustVariable);

        if ($isDefine) {
            $this->context[$this->callLevel][$name] = 0;//是什么类型变量
        }else {
            $name = $this->fixVariable($name);
        }
        $ns   = explode('.', $name);
        $name = array_shift($ns);
        if ($ns) {
            foreach ($ns as &$n) {
                if (substr($n, 0, 1) !== '$') {
                    $n = '\''.$n.'\'';
                }else {
                    $n = $this->parsedVariable($n);
                }
            }
            $name .= '['.implode('][', $ns).']';
        }

        return $name;
    }

    /**
     * 提取引号内的内容，以免干扰后续的解析
     *
     * @param $content
     */
    protected function extractQuote(&$content)
    {
        $trope = 0;
        $s     = -1;
        $index = 0;

        for ($i = 0, $l = strlen($content); $i < $l; $i++) {
            if ($content[$i] === '\'' && !$trope) {
                if ($s === -1) {
                    $s = $i;
                }else {
                    $holder                   = "'_{$index}'";
                    $this->quoteList[$holder] = substr($content, $s, $i - $s + 1);
                    $s                        = -1;
                    $index++;
                }
            }elseif ($content[$i] === '\\' && !$trope) {
                $trope = true;
            }else {
                $trope = false;
            }
        }

        if ($s !== -1) {
            $this->error('错误的引号。', $s + 2);
        }

        foreach ($this->quoteList as $holder => $c) {
            $content = str_replace($c, $holder, $content);
        }
    }

    /**
     * 将提取出来的引号中的内容还原回去
     *
     * @param $content
     */
    protected function fillQuote(&$content)
    {
        foreach ($this->quoteList as $h => $c) {
            $content = str_replace($h, $c, $content);
        }
    }

    /**
     * 解析标签
     *
     * @param string $tag
     * @param string $content
     */
    protected function parseTag(&$tag, &$content)
    {
        $this->parseExpress($content, $tag);
        switch ($tag) {
            case 'express':
            case 'void':
                break;
            case 'if':
                $content = 'if('.$content.'){';
                break;
            case 'elseif':
                $content = '}elseif('.$content.'){';
                break;
            case 'else':
                $content = '}else{';
                break;
            case 'while':
            case 'for':
            case 'foreach':
                $this->callLevel++;
                $content = $tag.'('.$content.'){';
                break;
            case 'function':
                $this->callLevel++;
                $content = 'function '.$content.'{';
                break;
            case 'ifEnd':
                $content = '}';
                break;
            case 'whileEnd':
            case 'forEnd':
            case 'foreachEnd':
            case 'functionEnd':
                $this->callLevel--;
                $content = '}';
                break;
            case 'do':
                $this->doStack[$this->callLevel] = $content;

                $content = 'do{';
                break;
            case 'doEnd':
                $content = '}while('.$this->doStack[$this->callLevel].')';
                break;
            case 'extends':
            case 'block':
            case 'include':
                if (substr($content, 0, 1) === "'" && substr($content, -1) === "'") {
                    $content = substr($content, 1, -1);
                }else {
                    $this->error('标签 '.$tag.' 的值应该由"\'"包裹。');
                }
                break;
            case 'let'://赋值
                $name = explode('=', $content)[0];
                if ($name === self::DATA_NAME) {
                    $this->error('定义的变量名不能是: '.self::DATA_NAME);
                }elseif (substr($name, 0, 6) === self::DATA_NAME.'[') {
                    $this->error('不允许将变量定义在 '.self::DATA_NAME.' 下。');
                }
                $this->checkVariable($name, true);
                break;
            case 'const':
                $name = explode('=', $content)[0];
                if (substr($name, 0, 1) === '$') {
                    $this->error('常量名不能以"$"为前缀。');
                }
                $content = 'const '.$content;
                break;
        }
    }

    /**
     * 搜集标签的编译结果
     */
    protected function collectTag()
    {
        $tag = 'express';
        foreach (static::$labels as $index => $label) {
            $labelLength = strlen($label);
            if (substr($this->content, $this->offset, $labelLength) === $label) {
                $this->offset += $labelLength;
                $tag          = $index;
                break;
            }
        }

        if ($tag === 'php') {
            $var     = substr($this->content, $this->offset, $this->contentLength());
            $varList = explode(',', $var);
            foreach ($varList as $v) {
                $this->parsedVariable($v, true);
            }
            $this->tagStack[] = ['php', $this->line, $this->tagPos];
            $content          = $this->collectBlockContent($this->config['leftDelimiter'].'/php'.$this->config['rightDelimiter']);
        }elseif ($tag === 'literal') {
            $this->contentLength();
            $this->tagStack[] = ['literal', $this->line, $this->tagPos];
            $content          = $this->collectBlockContent($this->config['leftDelimiter'].'/literal'.$this->config['rightDelimiter']);
        }else {
            $content = substr($this->content, $this->offset, $this->contentLength());
            $this->checkTag($tag);
            $this->parseTag($tag, $content);

            if ($tag === 'extends' || $tag === 'include') {
                $content  = self::joinPath($content, dirname($this->tpl));
                $pathInfo = pathinfo($content);
                if (!isset($pathInfo['extension'])) {
                    $content .= $this->config['suffix'];
                }
                $this->chain[$tag][$content] = [count($this->parsedStream), $this->tagPos];
                $this->checkCircularReference($content);
            }
        }

        $this->pushParsedStream($tag, $content);
    }

    /**
     * 搜集模板中出现的无需解析的块内容
     *
     * @param string $end 结束符号
     * @param string $content 搜集到的内容
     * @return string
     */
    protected function collectBlockContent($end, $content = '')
    {
        $endPos = strpos($this->content, $end, $this->offset);

        if ($endPos === false) {
            $content       .= substr($this->content, $this->offset);
            $this->content = fgets($this->fh);
            if (!$this->content) {
                return '';
            }

            $this->line++;
            $this->offset = 0;
            $content      = $this->collectBlockContent($end, $content);
        }else {
            $content      .= substr($this->content, $this->offset, $endPos - $this->offset);
            $this->offset = $endPos + strlen($end);
            array_pop($this->tagStack);
        }

        return $content;
    }

    /**
     * 判断标签的类型
     *
     * @param string $content
     * @param int    $offset 开始的位置
     * @param array  $labels 标签的选择范围
     * @return array
     */
    protected function adjustTagType($content, $offset, $labels)
    {
        $minPos = -1;
        $type   = 'literal';
        foreach ($labels as $index => $label) {
            $pos = strpos($content, $label, $offset);
            if ($pos > -1 && ($minPos == -1 || $pos < $minPos)) {
                $minPos = $pos;
                $type   = $index;
                if ($pos === 0) {
                    break;
                }
            }
        }

        return [$type, $minPos];
    }

    /**
     * 编译一行模板
     */
    protected function parseLine()
    {
        list($type, $pos) = $this->adjustTagType($this->content, $this->offset, $this->startLabel);
        if ($type === 'literal') {
            $this->pushParsedStream('literal', substr($this->content, $this->offset));

            return;
        }
        if ($pos - $this->offset > 0) {
            $this->pushParsedStream('literal', substr($this->content, $this->offset, $pos - $this->offset));
        }
        $this->tagPos = $pos + 1;
        $this->offset = $pos + strlen($this->startLabel[$type]);

        switch ($type) {
            case 0://leftDelimiter
                $this->tagStack[] = [$this->config['leftDelimiter'].'*', $this->line, $this->tagPos];
                $this->collectBlockContent('*'.$this->config['rightDelimiter']);
                break;
            //case 1:// //
            //    $this->offset += strlen($this->content);
            //    break;
            case 1:// /*
                $this->collectTag();
                break;
            default:
                break;
        }
        if ($this->offset < strlen($this->content)) {
            $this->parseLine();
        }
    }

    /**
     * 检查编译结果
     */
    protected function validateParsed()
    {
        foreach ($this->tagStack as $tag) {
            $this->line   = $tag[1];
            $this->tagPos = $tag[2];
            $this->error($tag[0].' 标签未关闭。');
        }
    }

    /**
     * 检查某标签内容的解析结果是否有错
     *
     * @param string $type
     */
    protected function checkTag($type)
    {
        switch ($type) {
            case 'extends':
                foreach ($this->parsedStream as $con) {
                    if ($con[0] === 'extends') {
                        $this->error('"extends" 标签出现多次,一个文件里只允许放置一个"extends"标签。');
                    }
                    if (!in_array($con[0], self::$allowAtExtendsBefore)) {
                        $this->error('标签 extends 不允许出现在 '.$con[0].' 之后。');
                    }
                }
                break;
            case 'else':
            case 'elseif':
                $tag = array_pop($this->tagStack);
                if (!$tag || $tag[0] !== 'if') {
                    $this->error(self::$labels[$type].' 错误的标签,没有对应的开始标签: if. ');
                }
                $this->tagStack[] = $tag;
                break;
            default:
                if (isset(self::$labels[$type.'End'])) {
                    $this->tagStack[] = [$type, $this->line, $this->tagPos];
                }elseif (substr($type, -3) === 'End') {
                    $tag = array_pop($this->tagStack);
                    if (!$tag || $tag[0].'End' !== $type) {
                        $this->error(self::$labels[$type].' 错误的关闭标签,没有对应的开始标签: '.self::$labels[substr($type, 0, -3)].'. ');
                    }
                }
                break;
        }
    }

    /**
     * 编译模板
     */
    protected function parse()
    {
        if ($this->isPeriod()) {
            return;
        }

        $this->fh = $this->openSourceFile();
        $this->initEvn();

        while ($this->content = fgets($this->fh)) {
            $this->line++;
            if ($this->content === "\n") {
                continue;
            }
            $this->offset = 0;
            $this->parseLine();
        }
        fclose($this->fh);
        $this->validateParsed();
        $this->writeParsed();
    }

    /**
     * 拼接地址
     *
     * @param string $path
     * @param string $dir
     * @return string
     */
    public static function joinPath($path, $dir = '')
    {
        if (substr($path, 0, 1) !== '/') {
            $path = $dir.'/'.$path;
        }

        if (strpos($path, './') === false) {
            return $path;
        }

        $ps    = explode('/', $path);
        $paths = [];
        foreach ($ps as $p) {
            if ($p === '..') {
                array_pop($paths);
            }elseif ($p !== '.') {
                array_push($paths, $p);
            }
        }

        return implode('/', $paths);
    }

    /**
     * 解析指定的文件并获得解析结果
     *
     * @param       $tpl
     * @param array $config
     * @return self
     */
    public static function parseFile($tpl, $config)
    {
        $compiler = new self($tpl, $config);
        $compiler->parse();
        $compiler->link();

        return $compiler;
    }

    /**
     * @param self $compiler
     */
    protected function replaceBlock($compiler)
    {
        $parent = $compiler->parsedStream;
        foreach ($this->blockAddress as $name => $childAddressList) {
            if (count($childAddressList) >= 2) {
                //TODO: 警告，有同名block
            }
            $childAddress = $childAddressList[0];
            if (count($childAddress) >= 2) {
                continue;
            }
            if (isset($compiler->blockAddress[$name])) {
                $addresses = $compiler->blockAddress[$name];
                foreach ($addresses as $address) {
                    $node = &$parent;
                    foreach ($address as $pos) {
                        $node = &$node[$pos];
                    }
                    $node = $this->parsedStream[$childAddress[0]];
                }
            }
        }
        $this->blockAddress = $compiler->blockAddress;
        $this->parsedStream = $parent;
    }

    /**
     * 应用include和extends
     */
    protected function link()
    {
        foreach ($this->chain as $tag => $chain) {
            foreach ($chain as $content => $info) {
                $compiler            = new self($content, $this->config);
                $compiler->callLevel = $this->callLevel;
                $compiler->context   = $this->context;

                $compiler->parse();
                $compiler->link();

                $this->context = $compiler->context;
                if ($tag === 'include') {
                    $this->parsedStream[$info[0]][1] = $compiler->getParsed();
                }else {
                    $this->replaceBlock($compiler);
                }
            }
        }
    }

    protected function genParsedFile($tpl)
    {
        $info = pathinfo($tpl);

        return $this->config['runtimePath'].'/parse'.str_replace($this->config['basePath'], '',
                $info['dirname'].'/'.$info['filename']).'.php';
    }

    /**
     * 将解析结果写入文件缓存
     */
    private function writeParsed()
    {
        $file = $this->genParsedFile($this->tpl);
        $dir  = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($file, "<?php return ".var_export($this->parsedStream, true).';', LOCK_EX);
        $this->saveDependency();
    }

    /**
     * 将解析语法树生成解析结果
     *
     * @param      $parsed
     * @param bool $inPhp
     * @return string
     */
    protected function joinParsed($parsed, $inPhp = false)
    {
        $obj = '';
        foreach ($parsed as $c) {
            if (in_array($c[0], $this->ignoreTag)) {
                continue;
            }
            if ($c[0] === 'include' || $c[0] === 'block') {
                $obj .= $this->joinParsed($c[1], $inPhp);
            }elseif ($c[0] === 'literal') {
                if ($inPhp) {
                    $obj   .= "?>";
                    $inPhp = false;
                }
                $obj .= $c[1];
            }else {
                if (!$inPhp) {
                    $obj   .= "<?php ";
                    $inPhp = true;
                }

                if (substr($c[1], -1) !== '{' && substr($c[1], -1) !== '}' && $c[0] !== 'php') {
                    $c[1] .= ';';
                }
                if ($c[0] === 'express') {
                    $c[1] = 'echo '.$c[1];
                }
                $obj .= $c[1];
            }
        }
        if ($inPhp) {
            $obj .= "?>";
        }

        return $obj;
    }

    /**
     * 获取解析出的语法树
     *
     * @return array
     */
    public function getParsed()
    {
        return $this->parsedStream;
    }

    /**
     * 开始解析模板
     */
    public function compile()
    {
        $this->parse();
        $this->link();

        return $this->joinParsed($this->parsedStream);
    }
}
