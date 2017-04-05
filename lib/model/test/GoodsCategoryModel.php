<?php
/**
 * Created by www.51ydgymall.cn
 * User: Rocky
 * Date: 2015/6/2
 * Time: 17:30
 *
 * 商品分类表
 */

namespace lib\model\test;


use jt\Model;

class GoodsCategoryModel extends Model
{
    protected        $itemModel = '\sys\model\resource\goods\GoodsModel';
    protected        $table     = 'resource.goods_category';
    protected static $columns   = [
        'id'               => 'uuid primary',
        'parentId'         => 'field:parent_id uuid',
        //'identifier'       => 'varchar:36',//标识，全局不允许重复 标识头部必须与上一级标识相同 比如上级标识为 2130 此类标识为2130XX
        'code'             => 'varchar:24',//编码,全局不允许重复
        'name'             => 'varchar:50',
        'uriPath'          => 'field:uri_path varchar:32',//分类路径 用于生成页面路径，便于SEO,可只需定义本处名称，然后自动拼接父路径
        'extend'           => 'text array',//扩展信息 用于产品行为、业务规则控制
        'model'            => 'text array',//产品规格模板
        'property'         => 'text array',//属性模板 属性可于前端直接显示
        'nodeCount'        => 'field:node_count int4',
        'nodeProgenyCount' => 'field:node_progeny_count int4',
        'itemCount'        => 'field:item_count int4',
        'itemProgenyCount' => 'field:item_progeny_count int4',
        'status'           => 'int2',
        'rank'             => 'int2',//排序
        'createAt'         => 'field:create_at timestamp at:create',
        'updateAt'         => 'field:update_at timestamp at:update',
        'del'              => 'bool del'
    ];

    /**
     * 通过ID获取子类
     *
     * @param $id
     * @return array
     */
    public function getChildById($id)
    {
        return $this->equals('parentId', $id)->fetch('id, name, code, nodeCount');
    }

    /**
     * 获取商品列表
     *
     * @param $page
     * @param $keywords
     * @return array
     */
    public function getList($page, $keywords)
    {
        if(!empty($keywords)){
            $this->search(['name', 'code'], "%{$keywords}%");
        }

        //var_dump(111);
        return $this->page($page['pageSize'], $page['page'])->fetchWithPage();
    }
}