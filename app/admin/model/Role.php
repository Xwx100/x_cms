<?php
// +----------------------------------------------------------------------
// | Date:   2020/12/17 0017
// +----------------------------------------------------------------------
// | Author: Administrator
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 角色表
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\Change;
use think\Model;
use think\model\relation\BelongsToMany;
use xu\helper\Arr;

class Role extends Base
{

    // 配置 表字段常量（数据库）
    const ROLE_ID = 'role_id';
    // 配置 表字段常量（前端）
    const ROLE_IDS = 'role_ids';

    // 模型对应数据表字段及类型
    protected $schema = [
        self::ID        => 'integer',
        self::NAME      => 'string',
        self::CREATE_AT => 'string',
        self::UPDATE_AT => 'string',
        self::CREATE_BY => 'string',
        self::UPDATE_BY => 'string',
    ];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // 开启自动写入用户字段
    protected $autoWriteUser = true;

    // 配置 模型自动转换的字段及类型
    protected $type = [
        self::CREATE_AT => 'datetime',
        self::UPDATE_AT => 'datetime',
    ];

    // +----------------------------------------------------------------------
    // | 读
    // +----------------------------------------------------------------------

    /**
     * 通过 角色ids 获取 权限
     * @param int|array $roleIds
     */
    public function getPermissionIdsByRoleIds($roleIds)
    {
        return $this->indexChange([
            Change::INPUT_FIELD => [static::ID, RolePermission::DATA_IDS, Uri::URI_IDS, User::USER_IDS],
            Change::INPUT_WHERE => [
                static::ID => $roleIds
            ],
            Change::INPUT_GROUP => [
                static::ID
            ],
            Change::INPUT_PAGE  => [
                Change::INPUT_PAGE_LIMIT => false
            ],
        ])->select();
    }

    public function getPermissionByUserId($userIds)
    {
        return $this->indexChange([
            Change::INPUT_FIELD => [RolePermission::DATA_IDS, Uri::URI_IDS],
            Change::INPUT_WHERE => [
                User::USER_ID => $userIds
            ],
            Change::INPUT_GROUP => [
                User::USER_ID
            ],
            Change::INPUT_PAGE  => [
                Change::INPUT_PAGE_LIMIT => false
            ],
        ])->select();
    }

    public function index(array $params)
    {
        $query = $this->indexChange($params);
        $page  = $this->indexCount($query);
        $list  = $query->select();

        return [
            'list' => $list,
            'page' => $page
        ];
    }

    /**
     * @param array $params
     * @param bool $closeFr
     * @param bool $closeJr
     * @return \think\db\Query
     */
    public function indexChange(array $params, $closeFr = false, $closeJr = false)
    {
        $dataIdsKey  = RolePermission::DATA_IDS;
        $dataTypeKey = RolePermission::PERMISSION_TYPE_DATA;
        $uriIdsKey   = RolePermission::URI_IDS;
        $uriTypeKey  = RolePermission::PERMISSION_TYPE_URI;
        $typeKey     = RolePermission::PERMISSION_TYPE;

        $this->change(Change::renewInstance()
            ->prepareBase(static::class)
            ->closeFr($closeFr)
            ->closeJr($closeJr));

        return $this->change
            ->prepareFr(
                [
                    RolePermission::PERMISSION_ID => [
                        Change::FR_SQL        => RolePermission::PERMISSION_ID,
                        Change::FR_SQL_FORMAT => [
                            "group_concat(case when {$typeKey} = '{$dataTypeKey}' then {%sql} else null end) as {%sql_alias}",
                            "group_concat(case when {$typeKey} = '{$uriTypeKey}' then {%sql} else null end) as {%sql_alias}",
                        ],
                        Change::FR_NO_GROUP   => true,
                        Change::FR_SQL_SWAP   => [$dataIdsKey, $uriIdsKey]
                    ],
                    User::USER_ID                 => [
                        Change::FR_SQL        => User::USER_ID,
                        Change::FR_SQL_FORMAT => 'group_concat({%sql}) as {%sql_alias}',
                        Change::FR_NO_GROUP   => true,
                        Change::FR_SQL_SWAP   => User::USER_IDS
                    ]
                ]
            )
            ->prepareJr([
                Change::JR_MODEL_CLASS  => RolePermission::class,
                Change::JR_JOIN_ON_RULE => [
                    [static::class => static::ID, RolePermission::class => RolePermission::ROLE_ID],
                ],
                Change::JR_JOIN_TYPE    => RolePermission::LEFT_JOIN,
                Change::JR_JOIN_RULE    => [RolePermission::URI_IDS, RolePermission::DATA_IDS, RolePermission::PERMISSION_ID]
            ])
            ->prepareJr([
                Change::JR_MODEL_CLASS  => UserRole::class,
                Change::JR_JOIN_ON_RULE => [
                    [static::class => static::ID, UserRole::class => UserRole::ROLE_ID],
                ],
                Change::JR_JOIN_TYPE    => UserRole::LEFT_JOIN,
                Change::JR_JOIN_RULE    => [User::USER_IDS, User::USER_ID]
            ])
            ->prepareInput([
                Change::INPUT_ORDER => [[Change::INPUT_ORDER_FIELD => static::ID, Change::INPUT_ORDER_TYPE => static::ORDER_DESC]]
            ])
            ->input($params)
            ->run()
            ->output();
    }

    /**
     * @param $query
     * @param $change
     * @return array
     */
    public function indexCount($query)
    {
        return $this->change->doCloneToCountQuery($query);
    }

    /**
     * @return array
     */
    public function fieldGetIds(): array
    {
        return [static::ID, RolePermission::DATA_IDS, Uri::URI_IDS, User::USER_IDS];
    }

    public function getDataIdsAttr($value, $data)
    {
        return x_group_concat_to_arr($value);
    }

    public function getUriIdsAttr($value, $data)
    {
        return x_group_concat_to_arr($value);
    }

    public function getUserIdsAttr($value, $data)
    {
        return x_group_concat_to_arr($value);
    }

    // +----------------------------------------------------------------------
    // | 写
    // +----------------------------------------------------------------------

    public function setUriIdsAttr($value, $data)
    {
        if (empty($value)) {
            return;
        }
        $this->commonSetIds(
            $this->permissions(),
            [$value, ['permission_type' => RolePermission::PERMISSION_TYPE_URI], true],
            '角色-uri权限'
        );
    }

    public function setUserIdsAttr($value, $data)
    {
        if (empty($value)) {
            return;
        }
        $this->commonSetIds(
            $this->users(),
            [$value],
            '角色-用户关联'
        );
    }

    public function setDataIdsAttr($value, $data)
    {
        if (empty($value)) {
            return;
        }
        $this->commonSetIds(
            $this->permissions(),
            [$value, ['permission_type' => RolePermission::PERMISSION_TYPE_DATA], true],
            '角色-uri权限'
        );
    }

    public function commonSetIds(BelongsToMany $modRelation, $saveAllArgs, $msg)
    {
        if ($this->isExists()) {
            $modRelation->detach();
        }
        $batch = $modRelation->saveAll(...$saveAllArgs);
        if (empty($batch)) {
            x_exception(x_str_f('%s 全量新增失败', $msg));
        }
        return $batch;
    }

    // +----------------------------------------------------------------------
    // | 关联定义
    // +----------------------------------------------------------------------
    /**
     * 获取 角色uri权限
     * @return \think\model\relation\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Uri::class, RolePermission::class, RolePermission::PERMISSION_ID, RolePermission::ROLE_ID);
    }

    /**
     * 获取 所有用户
     * @return \think\model\relation\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, UserRole::class);
    }
}