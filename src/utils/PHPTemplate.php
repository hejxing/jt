<?php
/**
 * auth: ax
 * Date: 2016/10/9 23:52
 */

namespace jt\utils;

class PHPTemplate
{
    protected $config = [
        'basePath' => '',
        'suffix'   => '.tpl'
    ];

    public function __construct($config)
    {
        $this->config = array_replace_recursive($this->config, $config);
    }

    public function render($tpl, $data)
    {
        ob_start();
        ini_set('display_errors', true);
        require $this->config['basePath'].$tpl.$this->config['suffix'];

        return ob_get_clean();
    }
}