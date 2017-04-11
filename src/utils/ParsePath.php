<?php
/**
 * @Copyright csmall.com
 * Auth: ax
 * Create: 2016/4/15 11:44
 */

namespace jt\utils;


class ParsePath
{
    /**
     * 解析文件名
     *
     * @param string $name 新的命名或规则
     * @param string $oldName 旧有的名字
     * @param int    $index 当前上传的序号
     * @return string
     */
    public static function name($name, $oldName, $index = 0)
    {
        if($name == ''){
            return $oldName;
        }
        preg_match_all('/\{(.*?)\}/', $name, $m);
        foreach($m[0] as $i => $origin){
            switch($m[1][$i]){
                case 'filename':
                    $pathInfo = pathinfo($oldName);
                    $name     = str_replace($origin, $pathInfo['filename'], $name);
                    break;
                case 'extension':
                    $pathInfo = pathinfo($oldName);
                    $name     = str_replace($origin, $pathInfo['extension'], $name);
                    break;
                case 'index':
                    $name = str_replace($origin, $index, $name);
                    break;
                case '_index':
                    $name = str_replace($origin, $index == 0? '': '_'.$index, $name);
                    break;
                default:
                    $name = str_replace($origin, self::parseCode($m[1][$i]), $name);
                    break;
            }
        }

        return $name;
    }

    /**
     * 解析目录名
     *
     * @param string $folder 文件路径及文件名
     * @return string
     */
    static public function folder($folder)
    {
        if($folder === '.'){
            return '';
        }
        if(preg_match_all('/\{(.*?)\}/', $folder, $m)){
            foreach($m[0] as $i => $origin){
                $folder = str_replace($origin, self::parseCode($m[1][$i]), $folder);
            }
        }

        return $folder;
    }

    /**
     * 解析路径
     *
     * @param string $path 新的路径或规则
     * @param string $oldName 旧有的名字
     * @param int    $index 当前上传的序号
     * @return string
     */
    static public function path($path, $oldName, $index = 0)
    {
        if($path == ''){
            return $oldName;
        }
        $dirName  = self::folder(dirname($path));
        $baseName = self::name(basename($path), $oldName, $index);

        return $dirName.($dirName? DIRECTORY_SEPARATOR: '').$baseName;
    }

    /**
     * 解路径中的变量
     *
     * @param $code
     * @return string
     */
    static private function parseCode($code)
    {
        $resolve = explode(':', $code);
        switch($resolve[0]){
            case 'date':
                $v = date(isset($resolve[1])? $resolve[1]: 'Y/M/D');
                break;
            case 'time':
                $v = date(isset($resolve[1])? $resolve[1]: 'H');
                break;
            case 'mtime':
                $v = sprintf('%1.0f', microtime(true) * 1000000, 1);
                break;
            case 'hexmtime':
                $v = dechex(microtime(true) * 1000000);
                break;
            case 'uuid':
                $v = Helper::uuid(isset($resolve[1])? json_decode('{'.$resolve[1].'}'): [], '-');
                break;
            default:
                $v = $resolve[0];
                break;
        }

        return $v;
    }

    /**
     * 解析路径,去除路径中的 ..
     * @param $path
     * @return string
     */
    public static function realPath($path){
        $parts = explode('/', $path);
        $ps = [];
        foreach($parts as $name){
            if($name === '..'){
                array_pop($ps);
            }else{
                $ps[] = $name;
            }
        }
        return implode('/', $ps);
    }
}