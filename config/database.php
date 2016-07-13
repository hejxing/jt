<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/3
 * Time: 21:45
 */

return [
    '__base'    => [
        'dBPrefix'    => '',
        'tablePrefix' => '',
        'type'        => 'pgsql',
        'charset'     => 'UTF8',
        'host'        => 'test.csmall.com',
        'port'        => '5432',
        'schema'      => 'silver_bag',
        'user'        => 'postgres',
        'password'    => 'wskiliwallens',
        'timeout'     => 15 //s
    ],
    'generic'   => [

    ],
    'community' => [
        'schema'   => 'community',
        'user'     => 'community',
        'password' => '69846d0e46a44e64317541b8e90924730'
    ]
];