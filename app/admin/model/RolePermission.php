<?php
// +----------------------------------------------------------------------
// | Date:   2020/12/17 0017
// +----------------------------------------------------------------------
// | Author: Administrator
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\XAdmin;
use think\model\Pivot;

class RolePermission extends Pivot implements XAdmin
{
    // 配置 关联表连接库
    protected $connection = self::X_ADMIN;

    // 配置 表字段常量（数据库）
    const ROLE_ID              = Role::ROLE_ID;
    const PERMISSION_ID        = 'permission_id';
    const PERMISSION_TYPE      = 'permission_type';
    const PERMISSION_TYPE_DATA = 'data';
    const DATA_IDS = 'data_ids';
    const DATA_ID = 'data_id';
    const URI_IDS = Uri::URI_IDS;
    const PERMISSION_TYPE_URI  = 'uri';

    // 配置 表字段
    protected $schema = [
        self::ROLE_ID         => 'integer',
        self::PERMISSION_ID   => 'string',
        self::PERMISSION_TYPE => 'string',
    ];
}