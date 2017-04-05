<?php
/**
 * Auth: ax
 * Date: 2016/11/14 13:48
 */

namespace jt\utils\mind_tpl;


class SimpleCache
{
    /**
     * @var string 源文件
     */
    private $compileFile = '';
    /**
     * @var array 配置项
     */
    private $config = [];
    /**
     * @var array 渲染用到的数据
     */
    private $data = [];

    public function __construct($compileFile, $config, $data)
    {
        $this->compileFile = $compileFile;
        $this->config      = $config;
        $this->data        = $data;
    }

    /**
     * 创建缓存文件
     *
     * @param $file
     */
    public function create($file)
    {
        $code = file_get_contents($this->compileFile);
        unset($this->data['__cache']);
        $code = '<?php $data=array_replace_recursive('.var_export($this->data, true).', $data);?>'.PHP_EOL.$code;

        $dir = dirname($file);
        if(!file_exists($dir)){
            mkdir($dir, 0777, true);
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $data = $this->data;
        file_put_contents($file, $code, LOCK_EX);
    }
}