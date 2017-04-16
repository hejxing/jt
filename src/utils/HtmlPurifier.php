<?php
/**
 * Auth: ax
 * Date: 2017/3/26 14:59
 */

namespace jt\utils;


class HtmlPurifier
{
    /**
     * @var \HTMLPurifier
     */
    protected $purifier = null;
    /**
     * @var \HTMLPurifier_Config
     */
    private static $baseConfig = null;

    public function __construct($name)
    {
        $method = '';
        if(strpos($name, '::') !== false){
            list($name, $method) = explode('::', $name, 2);
        }
        $configurator = $name? (__CLASS__): $this;
        $method       = $method?: 'base';
        /** @var \HTMLPurifier_Config $config */
        $baseConfig = $this->createConfig();
        $config     = $configurator::$method($baseConfig);

        $this->purifier = new \HTMLPurifier($config);
    }

    public function process($html)
    {
        return $this->purifier->purify($html);
    }

    public function createConfig()
    {
        if(static::$baseConfig === null){
            $config     = \HTMLPurifier_Config::createDefault();

            //$cacheDir = RUNTIME_PATH_ROOT.'/cache/htmlPurifier';
            //if(!is_dir($cacheDir)){
            //    mkdir($cacheDir, 0755, true);
            //}
            //$config->set('Cache.SerializerPath', $cacheDir);

            $config->set('Cache.DefinitionImpl', null);
            static::$baseConfig = $config;

        }

        return clone(static::$baseConfig);
    }

    public static function base($config)
    {
        return $config;
    }

    public static function simple(\HTMLPurifier_Config $config)
    {
        $config->set('HTML.Allowed', 'div,img[src]');

        return $config;
    }

    public static function __init()
    {
        require '../lib/htmlPurifier/HTMLPurifier.standalone.php';
    }
}