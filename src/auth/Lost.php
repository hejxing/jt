<?php
/**
 * Auth: ax
 * Created: 2017/5/13 16:06
 */

namespace jt\auth;


class Lost extends Auth
{

    /**
     * 执行权限检查
     *
     * @return int 200,401,403
     */
    protected function auth()
    {
        $this->action->fail('未指定权限控制器', 'authorMissing');
        return 403;
    }

    /**
     * 访问结果过滤
     *
     * @return int
     */
    protected function filter()
    {
        return 403;
    }
}