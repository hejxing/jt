<?php

/**
 * Auth: ax@jentian.com
 * Create: 2015/10/20 12:01
 * Version: 1.0
 */
namespace sys\model;

use jt\Model;

class User extends Model
{
    protected        $table   = 'admin.account';
    protected static $columns = [
        'id'       => 'uuid primary',//用户ID
        'account'  => 'varchar:36 lower',//账号，可以是手机、邮箱或是账号
        'sex'      => 'int2',
        'email'    => 'varchar:36',
        'mobile'   => 'varchar:15',
        'name'     => 'varchar:8',
        'salt'     => 'char:32 hidden',
        'password' => 'char:32 hidden',
        'role'     => 'uuid',
        'power'    => 'text',
        'createAt' => 'field:create_at timestamp at:create',
        'updateAt' => 'field:update_at timestamp at:update',
        'del'      => 'bool del'
    ];

    /**
     * 管理员登录
     *
     * @param $account
     * @return array
     */
    public function getByAccount($account)
    {
        return $this->equals('account', $account)->withHidden()->first();
    }
}