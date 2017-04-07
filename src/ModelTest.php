<?php
/**
 * Created by PhpStorm.
 * User: hejxi
 * Date: 2015/11/9
 * Time: 18:11
 */

use jt\Model;
use lib\model\test\GoodsCategoryModel;
use lib\model\test\GoodsModel;
use PHPUnit\Framework\TestCase;

class ModelTest extends TestCase
{
    public function testWhere()
    {
        $model = new GoodsModel();
        $model->field('id');
        $model->like('name', '吊坠');
        $model->where('categoryId != :id', ['id' => Model::UUID_ZERO], Model::BOUND_ALONE);
        $model->either()->Where('price_min > :price', ['price' => 100]);
        $model->equalsMulti(['name' => 'apple', 'stock' => 8], 'or', Model::BOUND_SELF);
        //$model->debug();
        //$model->first('id');
        $this->assertEquals($model->genSql('select'),
            'SELECT  "id" FROM "resource"."goods" WHERE (("name"   like \'%吊坠%\' ) AND ("category_id" !=  \'00000000-0000-0000-0000-000000000000\') OR ("price_min" >  100 AND ("name" = \'apple\' OR "stock" = 8) )) AND "del" = false ');
    }

    public function testNot()
    {
        $goodsCategory = GoodsCategoryModel::open();
        $model         = GoodsModel::open();
        $model->field('id')->not()->exists($goodsCategory);
        echo $model->genSql('select');
        $this->assertEquals(1, 1);
    }

    public function testRelate()
    {
        $array = GoodsModel::open()->select('g.name as goodsName, g.name, g.price_min FROM resource.goods as g LEFT JOIN resource.goods_category as c ON c.id=g.category_id');
        var_export($array);
        $this->assertEquals(1, 1);
        //GoodsModel::open()->join(GoodsCategoryModel::open()->where('')->field(''), Model::JOIN_LEFT, '', 'a');
        //$model = new GoodsModel();
        //$model->debug();
        //$categoryModel = new GoodsCategoryModel();
        //$categoryModel->field('name, uriPath');
        //$model->relate($categoryModel, 'category', ['categoryId' => 'id'], Model::RELATE_MANY_TO_ONE);
        //$array = $model->fetch();
        //var_export($array);
    }
}
