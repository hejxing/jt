<?php
/**
 * @Copyright jentian.com
 * Auth: hejxi
 * Create: 2016/4/15 10:47
 */

namespace jt\utils;


class Upload
{
    /**
     * 获取收传的文件
     *
     * @param string $fieldName upload_field name
     * @param string $saveTo 保存文件的目录
     * @return string 获取到的文件
     */
    public static function receive($saveTo, $fieldName = null)
    {
        self::check($fieldName);
        $loadList = [];
        if ($fieldName) {
            $loadList[] = $_FILES[$fieldName];
        }else {
            foreach ($_FILES as $item) {
                $loadList[] = $item;
            }
        }
        $index  = 0;
        $upList = [];
        foreach ($loadList as $item) {
            if (is_array($item['name'])) {//该域下有多个文件
                foreach ($item['name'] as $i => $k) {
                    $node = [];
                    foreach ($item as $key => $null) {
                        $node[$key] = $item[$key][$i];
                    }
                    $upList[] = self::move($node, $saveTo, $index);
                    $index++;
                }
            }else {
                $upList[] = self::move($item, $saveTo, $index);
                $index++;
            }
        }

        return $upList;
    }

    /**
     * 移动文件到上传目的地
     *
     * @param $item
     * @param $saveTo
     * @param $index
     * @return string 保存的文件
     * @throws \Exception
     */
    private static function move($item, $saveTo, $index)
    {
        $tmpFile    = $item['tmp_name'];
        $originFile = $item['name'];
        $saveFile   = ParsePath::path('{hexmtime}{_index}.{extension}', $originFile, $index);

        if (is_uploaded_file($tmpFile)) {
            if (!is_dir($saveTo) && !mkdir($saveTo, 0777, true)) {
                throw new \Exception('createSaveToFolderFail:创建文件上传目录 [' . $saveTo . '] 失败，可能是权限不够，请检查!');
            }
            move_uploaded_file($tmpFile, $saveTo . '/' .$saveFile);
        }

        return $saveFile;
    }

    /**
     * 查看上传进度
     */
    public static function progress($seed)
    {

    }

    /**
     * @param $fieldName
     * @throws \Exception
     */
    private static function check($fieldName)
    {
        if (strtolower($_SERVER['REQUEST_METHOD']) !== 'post') {
            throw new \Exception('uploadMethodIllegal:文件上传只能是post请求');
        }
        if ($fieldName && empty($_FILES[$fieldName])) {
            throw new \Exception('uploadFieldNameNotExists:本次请求中文件上传域不存在或不是一个有效的文件上传域');
        }
    }
}