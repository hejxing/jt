<?php
/**
 * Created by PhpStorm.
 * User: hejxing
 * Date: 2015/5/3
 * Time: 21:45
 */

$base = \jt\lib\database\Connector::readConfig(__DIR__ . '/..');
return array_replace_recursive($base, [
    '__base' => [
        //'host' => 'localhost',

        'schema' => 'silver_bag_dev'
    ]
]);