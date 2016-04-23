<?php

/**
 * @Copyright jentian.com
 * Auth: ax@jentian.com
 * Create: 2016/4/23 11:55
 */
namespace jt\compile\config;

class Config
{
    protected static $file = '';

    /**
     * 生成配置文件
     *
     * @param $file
     */
    public static function general($file)
    {
        self::$file = $file;
        $baseConfig = self::parse(PROJECT_ROOT . '/config/Config.php');
        $config     = self::parse(PROJECT_ROOT . '/config/' . RUN_MODE . '/Config.php');
        $config     = array_replace_recursive($baseConfig, $config);
        self::writeConfig($config);
    }

    /**
     * 将配置类转为数组
     *
     * @param $file
     * @return array
     */
    protected static function parse($file)
    {
        //读取Config
        $tokens = token_get_all(file_get_contents($file));

        $names  = [];
        $isName = false;
        $code   = 'return new class {';
        while (list(, $token) = each($tokens)) {
            if ($token === '{') {
                break;
            }
        }
        while (list(, $token) = each($tokens)) {
            if (is_array($token)) {
                if ($token[0] === T_CONST) {
                    $isName = true;
                }
                if ($isName && $token[0] === T_STRING) {
                    $isName  = false;
                    $names[] = $token[1];
                }
                $code .= $token[1];
            }else {
                $code .= $token;
            }
            if ($token === '}') {
                break;
            }
        }
        $class  = get_class(eval($code . ';'));
        $config = [];
        foreach ($names as $name) {
            $config[$name] = constant("$class::$name");
        }

        return $config;
    }

    /**
     * 写入配置文件
     *
     * @param $config
     */
    protected static function writeConfig($config)
    {
        $date = new \DateTime('now', new \DateTimeZone('Asia/Shanghai'));
        $con = '<?php' . PHP_EOL . '//CreateTime:' . $date->format('Y-m-d H:i:s') . PHP_EOL;
        $con .= 'class Config{' . PHP_EOL;
        foreach ($config as $name => $value) {
            $con .= '    const ' . $name . ' = ' . var_export($value, true) . ";\n";
        }
        $con .= '}';
        if (!is_dir(dirname(self::$file))) {
            mkdir(dirname(self::$file), 0777, true);
        }
        file_put_contents(self::$file, $con, LOCK_EX);
        chmod(self::$file, 0777);
    }
}