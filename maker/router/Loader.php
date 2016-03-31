<?php
/**
 * Auth: ax@jentian.com
 * Create: 2015/10/20 16:47
 */

namespace jt\maker\router;

use jt\Error;
use jt\exception\TaskException;

/**
 * Class Loader
 *
 * @package jt\maker
 */
abstract class Loader
{
    protected static $ignoreCache = false;

    protected static $root          = '';
    protected static $cacheFile     = '';
    protected static $cacheStore    = [];
    protected static $originCache   = [];
    protected static $parsedStore   = [];
    protected static $parseNewFile  = false;
    protected static $namespaceRoot = '';

    protected $dir             = '';
    protected $path            = '';
    protected $file            = '';
    protected $classInfo       = ['parseIgnore' => false];
    protected $line            = 0;
    protected $tokens          = [];
    protected $namespace       = '';
    protected $useList         = [];
    protected $class           = '';
    protected $method          = '';
    protected $params          = [];
    protected $commentLines    = [];
    protected $parsed          = [];
    protected $parsedContainer = [];
    protected $classType       = '';
    protected $commentBlocks   = [];

    abstract protected function parseComment();

    abstract protected function pack();

    /**
     * 要忽略的文件的列表
     *
     * @type array
     */
    protected static $ignoreFiles = ['.', '..', 'ErrorHandler.php', 'readme.md'];

    /**
     * Loader constructor.
     *
     * @param $path
     * @param $file
     */
    protected function __construct($path, $file)
    {
        $this->path = $path;
        $this->file = static::$root . $path . $file;
        $this->dir  = static::$root . $path;
    }

    private static function collectModules()
    {
        $modules = [];
        if (\substr(MODULE, 0, 4) === 'app\\') {
            $appRoot = PROJECT_ROOT . '/app';
            $hd      = \opendir($appRoot);
            if (\is_dir(PROJECT_ROOT . '/sys')) {
                $modules = ['sys' => [PROJECT_ROOT . '/sys', 'sys']];
            }
            while (($file = readdir($hd))) {
                if (in_array(strtolower($file), self::$ignoreFiles)) {
                    continue;
                }
                if (is_dir($appRoot . '/' . $file)) {
                    $modules['app_' . $file] = [$appRoot . '/' . $file, 'app\\' . $file];
                }
            }
        }else {
            $modules = [MODULE => [PROJECT_ROOT, '']];
        }

        return $modules;
    }

    /**
     * 解析源代码
     *
     * @return array
     */
    public static function parse()
    {
        //全局生成新的解析结果
        $modules = self::collectModules();

        $cacheRoot  = \Config::RUNTIME_PATH_ROOT . '/cache/parse';
        $cacheFiles = [];
        if (is_dir($cacheRoot)) {
            $hd = opendir($cacheRoot);
            while (($file = readdir($hd))) {
                if (!in_array($file, ['.', '..'])) {
                    $cacheFiles[$file] = 1;
                }
            }
        }

        $currentCache = [];

        foreach ($modules as $moduleName => $config) {
            list($dir, self::$namespaceRoot) = $config;

            static::$cacheStore = [];
            static::$root       = $dir . '/action';
            static::$cacheFile  = $cacheRoot . '/' . $moduleName . '.php';

            static::loadCache();
            static::traverseFile('/');
            static::processReference();
            static::saveCache();

            if (MODULE === $moduleName) {
                $currentCache = static::$cacheStore;
            }

            unset($cacheFiles[$moduleName . '.php']);
        }
        static::$cacheStore = $currentCache;

        foreach ($cacheFiles as $file => $v) {
            unlink($cacheRoot . '/' . $file);
        }
    }

    public static function processReference()
    {

    }

    private static function saveCache()
    {
        if (!static::$parseNewFile) {
            return;
        }
        if (!is_dir(dirname(static::$cacheFile))) {
            mkdir(dirname(static::$cacheFile), 0700, true);
        }
        //TODO: 自定义实现序列化
        file_put_contents(static::$cacheFile, "<?php\nreturn " . @var_export(static::$cacheStore, true) . ';');
    }

    private static function loadCache()
    {
        if (self::$ignoreCache && RUN_MODE === 'develop') {
            return;
        }
        if (file_exists(static::$cacheFile)) {
            static::$originCache = include(static::$cacheFile);
        }
    }

    /**
     * 从缓存中取值
     *
     * @return bool
     */
    private function loadFromCache()
    {
        $seed = filemtime($this->file);
        if (isset(static::$originCache['info'][$this->file]) && static::$originCache['info'][$this->file]['seed'] === $seed) {
            static::$cacheStore['info'][$this->file] = static::$originCache['info'][$this->file];
            if(!isset(static::$cacheStore['info'][$this->file]['class'])){//忽略解析
                return true;
            }
            $class = static::$cacheStore['info'][$this->file]['class'];
            foreach (static::$originCache as $type => $content) {
                if (isset($content[$class])) {
                    static::$cacheStore[$type][$class] = $content[$class];
                }
            }

            return true;
        }else {
            static::$cacheStore['info'][$this->file]['seed'] = $seed;

            return false;
        }
    }

    /**
     * 遍历文件
     *
     * @param $path
     *
     * @return array
     */
    private static function traverseFile($path)
    {
        if (!is_dir(static::$root . $path)) {
            return;
        }
        $hd = opendir(static::$root . $path);
        while (($file = readdir($hd))) {
            if (in_array(strtolower($file), self::$ignoreFiles)) {
                continue;
            }
            if (is_dir(static::$root . $path . $file)) {
                self::traverseFile($path . $file . '/');
            }else {
                $maker = new static($path, $file);
                $maker->parseFile();
            }
        }
    }

    abstract protected function collectGlobalValue();

    /**
     * 解析action文件
     *
     */
    private function parseFile()
    {
        if ($this->loadFromCache()) {
            return;
        }
        static::$parseNewFile = true;
        $this->tokens         = token_get_all(file_get_contents($this->file));
        $this->parseClass();
        $this->collectGlobalValue();
        if ($this->classInfo['parseIgnore']) {
            return;
        }
        reset($this->tokens);
        foreach ($this->commentBlocks as $index => $comment) {
            list($i) = each($this->tokens);
            for (; $i <= $index; $i++) {
                next($this->tokens);
            }
            $this->commentLines = $comment;
            $this->parseComment();
        }
        $this->fillParsed($this->classType);
    }


    /**
     * 存解析结果
     *
     * @param string $type
     */
    protected function fillParsed($type)
    {
        static::$cacheStore['info'][$this->file]['class'] = $this->class;
        static::$cacheStore['info'][$this->file]['type']  = $type;
        static::$cacheStore[$type][$this->class]          = $this->parsedContainer;
    }

    /**
     * 清洗注释
     *
     * @param $comment
     *
     * @return mixed
     */
    private function clearComment($comment)
    {
        $lines   = explode("\n", str_replace("\r", '', $comment));
        $lines   = array_slice($lines, 1, -1, true);
        $cleared = [];
        foreach ($lines as $index => $line) {
            $line = trim($line, " \t\n\r\0\x0B");
            if ($line === '') {//将空行去除
                continue;
            }
            $line = preg_replace('/^\* ?/', '', $line);
            if (preg_match('/^@(\w*) *(.*)/', $line, $match) || preg_match('/^(\w+): +(.*)/', $line, $match)) {
                list(, $name, $line) = $match;
            }else {
                $name = 'string';
            }
            $cleared[$index] = [$name, $line, $this->line + $index];
        }

        return $cleared;
    }

    /**
     * 解析出类名
     */
    private function parseClass()
    {
        while (list($index, $token) = each($this->tokens)) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_NAMESPACE:
                        $this->namespace = $this->collect([T_NS_SEPARATOR, T_STRING]);
                        break;
                    case T_CLASS:
                        $this->class = ($this->namespace ? '\\' . $this->namespace . '\\' : '') . $this->collect([T_STRING]);
                        $extend      = $this->collect([T_EXTENDS]);
                        if (!$extend) {
                            Error::fatal('ActionClassError', 'Action Class [' . $this->class . '] 不正确，该类必须为\jt\Action的子类，请检查');
                        }
                        break;
                    case T_USE:
                        $use = $this->collect([T_STRING, T_NS_SEPARATOR]);
                        if ($this->collect([T_AS])) {
                            $name = $this->collect([T_STRING, T_NS_SEPARATOR]);
                        }else {
                            $name = substr($use, strrpos($use, '\\') + 1);
                        }
                        if (strpos($use, '\\') !== 0) {
                            $use = '\\' . $use;
                        }
                        $this->useList[$name] = $use;
                        break;
                    case T_DOC_COMMENT:
                        $this->line                  = $token[2];
                        $this->commentBlocks[$index] = $this->clearComment($token[1]);
                        break;
                }
            }
        }
        reset($this->tokens);
    }

    /**
     * 解析命名空间
     *
     * @param array  $useful 需要搜集的内容
     * @param array  $ignore 需要忽略的内容
     * @param array  $break 遇此内容强制中止
     * @param string $glue 拼接符，如果为null表示返回array
     *
     * @return string
     */
    protected function collect(array $useful, array $ignore = [T_WHITESPACE], $break = [], $glue = '')
    {
        $collect = [];
        if (in_array('useful', $break)) {
            $break = array_merge($break, $useful);
            unset($break[array_search('useful', $break)]);
        }
        while (list(, $token) = each($this->tokens)) {
            if (!is_array($token)) {
                $token = [$token, $token, $this->line];
            }

            if (in_array($token[0], $useful)) {
                $collect[]  = $token[1];
                $this->line = $token[2];
            }elseif (!in_array('all', $ignore) && !in_array($token[0], $ignore)) {
                prev($this->tokens);
                break;
            }
            if (in_array($token[0], $break) || in_array('all', $break)) {
                break;
            }
        }

        return $glue === null ? $collect : implode($glue, $collect);
    }

    /**
     * 打印剩余的Token
     */
    protected function printTokens()
    {
        while (list(, $token) = each($this->tokens)) {
            var_export($token);
        }
    }

    /**
     * 指定出错位置
     *
     * @param $file
     * @param $line
     */
    protected function setErrorPos($file, $line)
    {
        $this->file = $file;
        $this->line = $line;
    }

    /**
     * 输出解析中的错误
     *
     * @param $code
     * @param $msg
     */
    protected function error($code, $msg)
    {
        $this->throwError(new TaskException($code . ':' . $msg));
    }

    /**
     * 输出解析中的错误
     *
     * @param \jt\exception\TaskException $e
     * @throws \jt\exception\TaskException
     */
    protected function throwError(TaskException $e)
    {
        throw new TaskException($e->getMessage() . ' in ' . $this->file . ' line ' . $this->line);
    }

    /**
     * 获取值
     *
     * @param      $name
     * @param bool $reset 是否重置游标
     *
     * @return string
     */
    protected function getValue($name, $reset = true)
    {
        if ($reset) {
            reset($this->commentLines);
        }
        while (list(, $item) = each($this->commentLines)) {
            if ($item[0] === $name) {
                $this->line = $item[2];

                return $item[1];
            }
        }

        return null;
    }

    /**
     * 从注解中获取指定类型值列表直到遇到未指定且非忽略或需中断的类型
     *
     * @param       $use
     * @param array $ignore
     * @param array $broke
     * @param bool  $reset
     * @return array
     */
    protected function getValueList($use, $ignore = [], $broke = [], $reset = false)
    {
        $list = [];
        if ($reset) {
            reset($this->commentLines);
        }
        while (list(, $item) = each($this->commentLines)) {
            if (in_array($item[0], $use)) {
                if ($item[1]) {
                    $list[] = $item;
                }
                $this->line = $item[2];
            }elseif (in_array($item[0], $broke) || (!in_array($item[0], $ignore) && array_search('all', $ignore) === false)) {
                $this->prev($this->commentLines);
                break;
            }
        }

        return $list;
    }

    /**
     * 指针向前移一位(解决指针向后移出界后不能再向前移的问题, 不知是否是PHP的BUG)
     *
     * @param array $data
     */
    protected function prev(array &$data)
    {
        $res = prev($data);
        if ($res === false) {
            end($data);
        }
    }

    /**
     * 通过类名寻找文件名
     *
     * @param $class
     * @return int|string
     */
    protected function getFileByClass($class)
    {
        foreach (static::$cacheStore['info'] as $file => $item) {
            if ($item['class'] === $class) {
                return $file;
            }
        }

        return '';
    }

    /**
     * 寻找指方法所在的行
     *
     * @param $class
     * @param $method
     * @return int
     */
    protected function getLineByMethod($class, $method)
    {
        $methods = static::$cacheStore['action'][$class]['methods'];
        foreach ($methods as $m) {
            if ($m['method'] === $method) {
                return $m['line'];
            }
        }

        return -1;
    }

    /**
     * 初始工作
     */
    public static function __init($cn)
    {
        if ($cn === __CLASS__) {
            foreach (self::$ignoreFiles as &$v) {
                $v = strtolower($v);
            }
        }
    }
}