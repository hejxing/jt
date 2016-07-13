<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2015/11/9
 * Time: 18:11
 */

namespace jt;


use lib\model\test\GoodsCategoryModel;
use lib\model\test\GoodsModel;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    public function testEqualsMulti()
    {
        $model = new GoodsModel();
        $model->like('name', '吊坠');
        $model->where('categoryId != :id', ['id' => Model::UUID_ZERO], Model::BOUND_ALONE);
        $model->orWhere('price_min > :price', ['price' => 100]);
        $model->equalsMulti(['name' => 'apple', 'stock' => 8], 'or', Model::BOUND_SELF);
        $model->debug();
        $model->first('id');
        //$this->assertEquals(1, 1);
    }

    public function testRelate()
    {
        $model = new GoodsModel();
        $model->debug();
        $categoryModel = new GoodsCategoryModel();
        $categoryModel->field('name, uriPath');
        $model->relate($categoryModel, 'category', ['categoryId' => 'id'], Model::RELATE_MANY_TO_ONE);
        $array = $model->fetch();
        var_export($array);
    }
}
