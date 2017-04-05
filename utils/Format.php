<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/6/4
 * Time: 15:37
 */

namespace jt\utils;


use jt\Validate;

class Format
{
    /**
     * 格式化金额值
     *
     * @param float $n 金额
     * @return float 保留两位精度的浮点数
     */
    public static function money($n)
    {
        return sprintf("%01.2f", is_numeric($n)? $n: 0);
    }

    /**
     * 将姓与名分离
     *
     * @param string $name 姓名
     * @return array [familyName, name]
     */
    public static function separateName($name)
    {
        if(!Validate::zh_cn($name) || mb_strlen($name) <= 1){
            return ['', $name];
        }

        $compound = '
            欧阳,太史,端木,上官,司马,东方,独孤,南宫,万俟,闻人,
            夏侯,诸葛,尉迟,公羊,赫连,澹台,皇甫,宗政,濮阳,公冶,
            太叔,申屠,公孙,慕容,仲孙,钟离,长孙,宇文,司徒,鲜于,
            司空,闾丘,子车,亓官,司寇,巫马,公西,颛孙,壤驷,公良,
            漆雕,乐正,宰父,谷梁,拓跋,夹谷,轩辕,令狐,段干,百里,
            呼延,东郭,南门,羊舌,微生,公户,公玉,公仪,梁丘,公仲,
            公上,公门,公山,公坚,左丘,公伯,西门,公祖,第五,公乘,
            贯丘,公皙,南荣,东里,东宫,仲长,子书,子桑,即墨,达奚,
            褚师,吴铭';

        $prefix = mb_substr($name, 0, 2);
        if(strpos($compound, $prefix) === false){
            return [mb_substr($name, 0, 1), mb_substr($name, 1)];
        }

        return [$prefix, mb_substr($name, 2)];
    }
}