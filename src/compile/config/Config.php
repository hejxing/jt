<?php

/**
 * @Copyright csmall.com
 * Auth: ax
 * Create: 2016/4/23 11:55
 */

namespace jt\compile\config;

use jt\compile\router\Router;

class Config
{
    protected static $parseFile = '';
    protected static $saveAs    = '';
    protected static $cache     = [];
    protected static $cacheFile = '';
    protected static $newParse  = false;
    protected static $maxLevel  = 16;
    protected static $filePool  = [];

    /**
     * 生成配置文件
     *
     * @param string $saveAs
     * @param string $file
     */
    public static function general($saveAs, $file)
    {
        self::checkFileExists($file);
        $file            = realpath($file);
        self::$saveAs    = $saveAs;
        self::$parseFile = $file;
        self::loadCache();
        self::parse($file);

        self::writeResult();
    }

    /**
     * 解析配置文件
     *
     * @param $file
     */
    protected static function parse($file)
    {
        self::$filePool[] = $file;
        if(count(self::$filePool) >= self::$maxLevel){
            self::lastWords('超出最大继承级数 ['.self::$maxLevel.'] 限定，有可能是重复继承，请检查'.PHP_EOL.var_export(self::$filePool, true));
        }
        $seed = filemtime($file);
        if(!isset(self::$cache['info'][$file]) || self::$cache['info'][$file]['seed'] !== $seed){
            $value                      = self::parseValue($file);
            self::$cache['info'][$file] = [
                'seed'   => $seed,
                'extend' => $value['extend']
            ];
            self::$cache[$file]         = $value['value'];
            self::$newParse             = true;
        }
        if(self::$cache['info'][$file]['extend']){
            self::parse(self::$cache['info'][$file]['extend']);
        }
    }

    protected static function genCacheFileName()
    {
        self::$cacheFile = RUNTIME_PATH_ROOT.'/cache/config/'.MODULE.'.php';
    }

    protected static function loadCache()
    {
        static::genCacheFileName();
        if(file_exists(self::$cacheFile)){
            /** @noinspection PhpIncludeInspection */
            self::$cache = include(self::$cacheFile);
        }
    }

    protected static function findDefaultConfig($file)
    {
        $defaultConfigFile = __DIR__.'/DefaultConfig.php';

        return $file === $defaultConfigFile? '': $defaultConfigFile;
    }

    protected static function parseExtend(array $tokens, $file)
    {
        foreach($tokens as $token){
            if($token[0] === T_DOC_COMMENT){
                $lines = explode("\n", str_replace("\r", '', $token[1]));
                foreach($lines as $line){
                    $line = str_replace(' ', '', $line);
                    if(substr($line, 0, 8) === '*@extend'){
                        $extend = substr($line, 8);
                        if(strpos($extend, '/') !== 0){
                            $extend = dirname($file).'/'.$extend;
                        }
                        $extend = str_replace('{RUN_MODE}', RUN_MODE, $extend);
                        $extend .= '.php';
                        //读取Config
                        self::checkFileExists($extend);

                        return realpath($extend);
                    }
                }
            }
        }

        return static::findDefaultConfig($file);
    }

    protected static function checkFileExists($file)
    {
        if(!file_exists($file)){
            self::lastWords('解析配置时需要的文件['.$file.']不存在!');
        }
    }

    protected static function fillCode($token, &$code)
    {
        if(is_array($token)){
            $code .= $token[1];
        }else{
            $code .= $token;
        }
    }

    /**
     * 将配置类转为数组
     *
     * @param $file
     * @return array
     */
    protected static function parseValue($file)
    {
        //读取Config
        $tokens = token_get_all(file_get_contents($file));
        $extend = self::parseExtend($tokens, $file);

        $names     = [];
        $functions = [];
        $code      = '';

        while(list(, $token) = each($tokens)){
            array_shift($tokens);
            if(is_array($token) && $token[0] === T_STRING && $token[1] === 'define'){
                $code .= $token[1];
                while(list(, $token) = each($tokens)){
                    array_shift($tokens);
                    self::fillCode($token, $code);
                    if($token === ';'){
                        break;
                    }
                }
            }
            if($token === '{'){
                $code .= 'return new class {';
                while(list(, $token) = each($tokens)){
                    self::fillCode($token, $code);
                }
            }
        }

        reset($tokens);

        while(list(, $token) = each($tokens)){
            if(is_array($token)){
                if($token[0] === T_CONST){
                    while(list(, $token) = each($tokens)){
                        if($token[0] === T_STRING){
                            $names[] = $token[1];
                            break;
                        }
                    }
                }
                if($token[0] === T_FUNCTION){
                    while(list(, $token) = each($tokens)){
                        if($token[0] === T_STRING){
                            $functions[] = $token[1];
                            break;
                        }
                    }
                }
            }
        }

        $class  = get_class(eval($code.';'));
        $config = [];
        foreach($names as $name){
            $config[$name] = constant("$class::$name");
        }

        foreach($functions as $name){
            $config[$name] = call_user_func([$class, $name]);
        }

        return [
            'extend' => $extend,
            'value'  => $config
        ];
    }

    protected static function genFileHeader()
    {
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $con  = '<?php'.PHP_EOL;
        $con  .= '//CreateTime: '.$date->format('Y-m-d H:i:s').PHP_EOL;
        $con  .= '//SourceFile: '.self::$parseFile.PHP_EOL;

        return $con;
    }

    protected static function combCache($file, &$cache = [])
    {
        $cache['info'][$file] = self::$cache['info'][$file];
        $cache[$file]         = self::$cache[$file];
        if(self::$cache['info'][$file]['extend']){
            self::combCache(self::$cache['info'][$file]['extend'], $cache);
        }else{
            if(!is_dir(dirname(self::$cacheFile))){
                mkdir(dirname(self::$cacheFile), 0777, true);
            }
            $con = self::genFileHeader();
            $con .= 'return '.var_export($cache, true).';';
            file_put_contents(self::$cacheFile, $con, LOCK_EX);
            chmod(self::$cacheFile, 0777);
        }
    }


    protected static function mergeResult($file)
    {
        $base = [];
        if(self::$cache['info'][$file]['extend']){
            $base = self::mergeResult(self::$cache['info'][$file]['extend']);
        }

        return array_replace_recursive($base, self::$cache[$file]);
    }

    /**
     * 生成配置文件
     *
     * @param $result
     * @return string
     */
    protected static function genCon($result)
    {
        $con = self::genFileHeader();
        $con .= 'class Config{'.PHP_EOL;


        foreach($result as $name => $value){
            $con .= '    const '.$name.' = '.var_export($value, true).";\n";
        }
        $con .= '}';

        return $con;
    }

    /**
     * 写入解析结果
     */
    protected static function writeResult()
    {
        if(!self::$newParse && file_exists(self::$saveAs)){
            return;
        }

        self::combCache(self::$parseFile);
        $result = self::mergeResult(self::$parseFile);

        $con = static::genCon($result);

        if(!is_dir(dirname(self::$saveAs))){
            mkdir(dirname(self::$saveAs), 0777, true);
        }
        file_put_contents(self::$saveAs, $con, LOCK_EX);
        chmod(self::$saveAs, 0777);

        Router::cleanCache();
    }

    public static function lastWords($words)
    {
        header('Content-type: text/plain;');
        echo $words, PHP_EOL;
        exit();
    }
}