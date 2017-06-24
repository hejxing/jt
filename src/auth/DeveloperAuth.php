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
        if(RUN_MODE === 'develop'){
            return 200;
        }else{
            if($this->mark === 'menu'){
                $this->action->fail('只允许在开发模式下使用此功能', 'onlyAllowDeveloper');
            }else{//可以要求开发人员登录
                return 200;
            }
        }

        return 403;
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