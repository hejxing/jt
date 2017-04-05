<?php

/**
 * @Copyright csmall.com
 * Auth: ax@csmall.com
 * Create: 2016/7/1 13:47
 */

namespace lib\model\test;

use jt\Model;

class GoodsModel extends Model
{
    protected        $table   = 'resource.goods';//SPU  商品表
    protected static $columns = [
        'id'            => 'uuid primary',
        'categoryId'    => 'field:category_id uuid',
        //'categoryIdentifier' => 'field:category_identifier varchar:36',//通过分类的identifier可快速找出其下属所有商品
        'type'          => 'int2',//0:普通商品(需要库存和发货流程)  10:虚拟商品(无需库存和发货流程)
        'uriName'       => 'field:uri_name varchar:24',//用于生成页面地址，便于SEO
        'name'          => 'varchar:255',
        'price_min'     => 'numeric', //规格中最小的价格
        'price_max'     => 'numeric', //规格中最大的价格
        'photo'         => 'varchar:255',//封面图、主图
        'imagesId'      => 'field:images_id uuid',//图库id,获取轮播图
        'stock'         => 'int8',
        'unit'          => 'varchar:12',//单位
        'code'          => 'varchar:24',//内部码,企业内部按自己的编码规则进行的编码 一码多品
        'barcode'       => 'varchar:16',//条形码,符合世界商品流通编码的EAN标准,一码多品
        'extend'        => 'text array',//商品扩展信息 {"$key":"$value", ...}  key:duration存款时长,key:durationUnit 存款时长单位，{y,m,d}
        'model'         => 'text array',//商品型号列表 {"color":["read","yellow"], "spec":["1KG","2KG"], ...}便于展示商品规格，具体规格的价格、库存、说明由商品规格表存储
        'property'      => 'text array',//{"$name":"$value", ...}属性模板 属性可用于前端直接显示
        'tag'           => 'varchar:255 array',//商品标签 用json数组存储
        'seoKeywords'   => 'field:seo_keywords varchar:255',
        'seoDescribe'   => 'field:seo_describe varchar:1024',
        'intro'         => 'varchar:1024',//商品简介
        'detail'        => 'text',//商品详细描述
        'clicks'        => 'int8',//打开详情页次数
        'saleCount'     => 'field:sale_count int8',//显示销量
        'realSaleCount' => 'field:real_sale_count int8',//实际销量
        'ver'           => 'int8',//版本号 编辑完成随即创建一份附本存入历史库
        'createAt'      => 'field:create_at timestamp at:create',
        'updateAt'      => 'field:update_at timestamp at:update',
        'del'           => 'bool del'
    ];

    /**
     * @return array
     */
    public static function getColumns(): array
    {
        return self::$columns;
    }
}