<?php
// +----------------------------------------------------------------------
// | Date:   2020/12/17 0017
// +----------------------------------------------------------------------
// | Author: Administrator
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 用户-角色表
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\XAdmin;
use think\model\Pivot;

class UserRole extends Pivot implements XAdmin
{
    // 配置 关联表连接库
    protected $connection = self::X_ADMIN;

    // 配置 表字段常量（数据库）
    const USER_ID = User::USER_ID;
    const ROLE_ID = Role::ROLE_ID;
    // 配置 表字段常量（前端）
    const ROLE_IDS = Role::ROLE_IDS;

    // 配置 表字段
    protected $schema = [
        self::USER_ID => 'integer',
        self::ROLE_ID => 'integer'
    ];
}