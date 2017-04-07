<?php
/**
 * Created by ax@csmall.com.
 * Date: 2015/7/4 11:19
 *
 *
 */

namespace jt;


class Context
{
    private $pool = [];

    public function current($name)
    {
        switch($name){
            case 'Controller':
                return Controller::current();
            case 'action':
                return Controller::current()->getAction();
        }

        return null;
    }

    public function push($name, $data)
    {
        $this->pool[$name] = $data;
    }
}