<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/11/16
 * Time: 15:05
 */

namespace jt\auth;


class DeveloperAuth extends Auth
{

    /**
     * 执行权限检查
     *
     * @return int 200,401,403
     */
    protected function auth()
    {
        return RUN_MODE === 'develop'? 200: 401;
    }

    /**
     * 访问结果过滤
     *
     * @return int
     */
    protected function filter()
    {
        return 200;
    }
}