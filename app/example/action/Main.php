<?php
/**
 * Auth: ax@jentian.com
 * Create: 2015/10/20 9:32
 *
 * @version 2.5
 * @title 用户账号 根据Model生成相应的接口说明文档
 * @desc 用户基础资料、登录等相关操作，对外接口为Restful风格
 * 基本操作是在Model中完成的，此处将通过框架自动将基出操作映射到外部
 * 用户只能对自身的资料进行管理，除登录外，其余操作皆需要身份认证
 * @notice 该类下的方法默认是需要身份认证的，对其他人资料进行修改需要具有管理员身份才能进行
 *
 * 以下是本类中的一些预设置和基础值
 *
 * @basePath /nm/
 * @defaultAuth public
 * @tplPathBase /user/
 * @defaultMime json
 */

/**
 * @ model sys\model\User
 * @ access get:public put:login list:public
 * @ router any /user auth:public
 */

namespace app\example\action;

use jt\Action;
use jt\exception\TaskException;

/**
 * @ access get:public put:login list:public
 * @ router any /users auth:public
 */
class Main extends Action
{

    /**
     * 首页
     *
     * @router get /index auth:public mime:html
     *
     */
    public function index()
    {
        $this->out('action', 'Hello');
        $this->out('name', 'world');
    }

    /**
     * 演示参数获取和参数验证
     *
     * @param \jt\Requester $query
     * size:int [default:20] 默认值为20
     * @param int           $page [default:1 min:1] 默认值为1
     *
     * @return array
     *
     * @router get /list/:page mime:json
     */
    public function turnPage($query, $page)
    {
        return [
            'page' => $page,
            'size' => $query->size
        ];
    }

    /**
     * 演示通配路径 比如可以访问 /test/414
     *
     * @param string        $path 获取到的路径
     * @param \jt\Requester $query
     *
     * @return array
     *
     * @router get /any/*path mime:html
     */
    public function defaultPage($path, $query)
    {
        echo $path;
        exit();
    }

    /**
     * 演示抛出一个404
     *
     * @throws \jt\exception\TaskException
     *
     * @router get /show/404 auth:public mime:html
     */
    public function custome404()
    {
        throw new TaskException('404', 404);
    }

    /**
     * 显示一个post请求
     *
     * @param \jt\Requester $body
     * name [string max:36 min:6] 名称
     * @param               $id [require] 产品ID
     *
     * @router post /product/:id mime:html
     */
    public function postMethod($body, $id)
    {

    }
}