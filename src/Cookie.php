<?php

/**
 * Auth: ax
 * Created: 2017/4/2 0:37
 */

namespace jt;

class Cookie
{
    /**
     * @param $config
     * @return $this
     */
    public static function create($config)
    {
        return new self($config);
    }
}