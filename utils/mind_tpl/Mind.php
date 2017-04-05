<?php
/**
 * Auth: ax
 * Date: 2016/10/29 16:20
 */

namespace jt\utils\mind_tpl;

use jt\TemplateInterface;

class Mind implements TemplateInterface
{
    static protected $dependency = [];

    protected $tpl          = '';
    protected $plugin       = [];
    protected $compiledFile = '';
    protected $compileInfo  = [];
    protected $uri          = '';
    protected $isCached     = false;
    protected $cacheFile    = '';
    protected $config       = [
        'basePath'      => '',
        'suffix'        => '.tpl',
        'compilePeriod' => 10, //0:不缓存 -1:一直缓存
        'runtimePath'   => RUNTIME_PATH_ROOT.'/mind_runtime/'.MODULE, //dependency.php, parsed/*, compile/*, cache/*
        'cachePath'     => RUNTIME_PATH_ROOT.'/mind_runtime/'.MODULE.'/cache'
    ];

    public function __construct(array $config)
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    public function render(string $tpl, array $data): string
    {
        $this->tpl          = $this->config['basePath'].$tpl.$this->config['suffix'];
        $this->compiledFile = $this->config['runtimePath'].'/compile'.$tpl.'.php';

        return $this->blend($data);
    }

    protected function isCompilePeriod()
    {
        /** @noinspection PhpIncludeInspection */
        $this->compileInfo = include($this->config['runtimePath'].'/compilePeriod.php');

        if(!$this->compileInfo){
            $this->compileInfo = [];
        }

        if(!isset($this->compileInfo[$this->tpl])){
            return false;
        }

        if($this->config['compilePeriod'] == -1){
            return true;
        }

        $compileTime = $this->compileInfo[$this->tpl]['time'];
        if(microtime(true) - $compileTime < $this->config['compilePeriod']){
            return true;
        }

        return false;
    }

    protected function freshCompileInfo()
    {
        $this->compileInfo[$this->tpl] = [
            'time' => microtime(true)
        ];
        file_put_contents($this->config['runtimePath'].'/compilePeriod.php', '<?php return '.var_export($this->compileInfo, true).';', LOCK_EX);
    }

    protected function compile()
    {
        if($this->isCompilePeriod()){
            return;
        }

        $compile = new Compile($this->tpl, [
            'runtimePath' => $this->config['runtimePath'],
            'basePath'    => $this->config['basePath'],
            'suffix'      => $this->config['suffix']
        ]);
        $content = $compile->compile();
        $dir     = dirname($this->compiledFile);

        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }

        file_put_contents($this->compiledFile, $content, LOCK_EX);
        $this->freshCompileInfo();
    }


    /**
     * @param $data
     * @return string
     */
    protected function blend($data): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        $data = array_replace_recursive($this->plugin, $data);
        ob_start();
        //ini_set('display_errors', true);

        if($this->isCached){
            /** @noinspection PhpIncludeInspection */
            include($this->cacheFile);
        }elseif(isset($data['__cache'])){
            $data = array_replace_recursive($data, $data['__cache']);
            $this->genCache($data);
            /** @noinspection PhpIncludeInspection */
            include($this->cacheFile);
        }else{
            $this->compile();
            /** @noinspection PhpIncludeInspection */
            include($this->compiledFile);
        }

        return ob_get_clean();
    }

    /**
     * 是否已经存在缓存
     *
     * @param string $uri
     * @param string $queryString
     * @return bool
     */
    public function hadCache(string $uri, string $queryString): bool
    {
        if($queryString){
            $uri .= '_q_'.md5($queryString);
        }
        $uri             .= '.php';
        $this->cacheFile = $this->config['cachePath'].$uri;
        $this->isCached  = file_exists($this->cacheFile);

        return $this->isCached;
    }

    /**
     * 生成缓存
     *
     * @param $data
     */
    public function genCache($data)
    {
        $this->compile();

        $cache = new SimpleCache($this->compiledFile, [
            'runtimePath' => $this->config['runtimePath']
        ], $data);

        $cache->create($this->cacheFile);
    }
}