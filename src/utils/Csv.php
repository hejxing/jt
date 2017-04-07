<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2016/4/14
 * Time: 15:27
 */

namespace jt\utils;


class Csv
{
    /**
     * csv文件
     *
     * @type string
     */
    public $file = '';
    /**
     * 导出的行数限制
     *
     * @type int
     */
    public $length    = 0;
    public $count     = 0;
    public $delimiter = ',';
    public $enclosure = '"';
    public $escape    = '\\';
    public $fieldMap  = [];

    /**
     * 将指定的CSV文件整理成数组
     *
     * @param string $file cs文件
     */
    public function __construct($file)
    {
        $this->file = $file;
    }

    public function getList($count = null)
    {
        if($count){
            $this->count = $count;
        }
        $handle = fopen($this->file, 'r');
        $list   = [];

        while($item = fgetcsv($handle, $this->length, $this->delimiter, $this->enclosure, $this->escape)){
            $node = [];
            foreach($this->fieldMap as $name => $key){
                if(is_callable($key)){
                    $node[$name] = $key($item);
                }else{
                    $node[$name] = $item[$key];
                }

            }
            $list[] = $node;
            if($this->count && $this->count <= count($list)){
                yield $list;
                $list = [];
            }
        }
    }
}