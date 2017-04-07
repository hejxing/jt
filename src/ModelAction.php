<?php
/**
 * @Auth ax@csmall.com
 * @Create 2015/10/23 15:55
 */

namespace jt;


class ModelAction extends Action
{
    public function get(Model $model, $id)
    {
        return $model->get($id);
    }

    public function post(Model $model, $post)
    {
        //$this->model->
    }

    public function put(Model $model, $id, $post)
    {

    }

    public function delete(Model $model, $id)
    {

    }

    public function getList(Model $model, $get)
    {
        //$this->model->
    }

    public function postList(Model $model, $post)
    {
        //$this->model->
    }

    public function putList(Model $model, $post)
    {

    }

    public function deleteList(Model $model, $get)
    {

    }
}