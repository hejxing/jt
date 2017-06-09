<?php

/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/8/4 19:30
 */

namespace jt\lib\mcrypt;

class Des
{
    var $key;
    var $iv; //偏移量

    function __construct($key, $iv = 0)
    {
        $this->key = $key;
        if($iv == 0){
            $this->iv = $key;
        }else{
            $this->iv = $iv;
        }
    }

    //加密
    function encrypt($str)
    {
        return openssl_encrypt($str, 'DES-EDE3-CBC', $this->key, 0, $this->iv);
    }

    //解密
    function decrypt($str)
    {
        return openssl_decrypt($str, 'DES-EDE3-CBC', $this->key, 0, $this->iv);
    }
}