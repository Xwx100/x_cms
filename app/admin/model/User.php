<?php
// +----------------------------------------------------------------------
// | Date:   2020/12/17 0017
// +----------------------------------------------------------------------
// | Author: Administrator
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 用户表
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\Change;
use think\Model;
use xu\helper\Arr;

class User extends Base
{
    // 配置 表字段常量（数据库）
    const USER_ID = 'user_id';
    const EN      = 'en';
    const PWD     = 'pwd';
    const VERIFY  = 'verify';
    // 配置 表字段常量（前端）
    const USER_IDS = 'user_ids';

    // 配置 表字段
    protected $schema = [
        self::ID        => 'integer',
        self::NAME      => 'string',
        self::EN        => 'string',
        self::PWD       => 'string',
        self::VERIFY    => 'string',
        self::CREATE_AT => 'string',
        self::UPDATE_AT => 'string',
    ];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 配置 模型自动转换的字段及类型
    protected $type = [
        self::CREATE_AT => 'datetime',
        self::UPDATE_AT => 'datetime',
    ];

    // +----------------------------------------------------------------------
    // | 读
    // +----------------------------------------------------------------------

    /**
     * 获取 用户信息 + 角色信息 + 权限信息
     * @param $name
     * @param $pwd
     */
    public function get($name, $pwd)
    {
        $value = [
            'user'  => [],
            'roles' => [],
            'uris'  => []
        ];

        $user = $this->getRoleIdsByName($name, $pwd);
        if ($userId = Arr::get($user, static::ID, [])) {
            $value['user'] = $this->getRowsByIds($userId)->findOrEmptyArray();
            if (empty($value['user'])) {
                return $value;
            }
        }

        if (($roleIds = Arr::get($user, Role::ROLE_IDS, [])) && $userId) {
            $role = Role::intance()->getPermissionByUserId($userId)[0];

            if ($uriIds = Arr::get($role, Uri::URI_IDS, [])) {
                $record              = [];
                $uris                = Uri::intance()->findFront($uriIds, $record);
                $value['uris']       = Arr::childRecursive($uris, 0, Uri::ID, Uri::URI_ID);
                $value['uris_front'] = Arr::childRebuild($value['uris'], function ($rows, $item, $childKey) {
                    if ($item[URI::LEVEL] != 3) {
                        $row            = $item[Uri::FRONT_ITEM];
                        $row[$childKey] = $item[$childKey];
                        array_push($rows, $row);
                    }
                    return $rows;
                });
                $value['uris_3']     = Uri::intance()->getLastUris($value['uris']);
            }

            $roles     = Role::intance()->getRowsByIds($roleIds)->select();
            $rolesById = Arr::itemValueToKey($roles->toArray(), static::ID);
            foreach ($roleIds as $roleId) {
                $value['roles'][] = (array)$rolesById[$roleId];
            }
        }

        return $value;
    }

    /**
     * 通过用户和密码 获取用户信息
     * @param $name
     * @param $pwd
     * @return Model
     */
    public function getRowByName($name, $pwd)
    {
        return $this->indexChange([
            Change::INPUT_FIELD => [],
            Change::INPUT_WHERE => [
                self::NAME => $name,
                self::PWD  => $pwd
            ]
        ], true, true)->findOrEmpty();
    }

    /**
     * 通过用户和密码 获取用户信息 和 角色 ids
     * @param $name
     * @param $pwd
     * @return Model
     */
    public function getRoleIdsByName($name, $pwd)
    {
        return $this->indexChange([
            Change::INPUT_FIELD => [static::ID, Role::ROLE_IDS],
            Change::INPUT_WHERE => [
                self::NAME => $name,
                self::PWD  => $pwd
            ]
        ])->findOrEmpty();
    }

    /**
     * @param array $params
     * @param bool $closeFr
     * @param bool $closeJr
     * @return array
     */
    public function index(array $params)
    {
        $query = $this->indexChange($params);
        $page  = $this->indexCount($query);

        $list = $query->select();

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
        $this->change(Change::renewInstance()
            ->prepareBase(static::class)
            ->closeFr($closeFr)
            ->closeJr($closeJr));

        return $this->change
            ->prepareFr(
                [
                    UserRole::ROLE_ID => [
                        Change::FR_SQL      => UserRole::ROLE_ID, Change::FR_SQL_FORMAT => 'group_concat({%sql}) as {%sql_alias}',
                        Change::FR_NO_GROUP => true, Change::FR_SQL_SWAP => UserRole::ROLE_IDS
                    ]
                ]
            )
            ->prepareWr([
                User::NAME      => [Change::WR_METHOD => 'like'],
                USER::CREATE_AT => [Change::WR_METHOD => 'between'],
                USER::UPDATE_AT => [Change::WR_METHOD => 'between'],
            ])
            ->prepareJr([
                Change::JR_MODEL_CLASS  => UserRole::class,
                Change::JR_JOIN_ON_RULE => [
                    [static::class => static::ID, UserRole::class => UserRole::USER_ID],
                ],
                Change::JR_JOIN_TYPE    => UserRole::LEFT_JOIN,
                Change::JR_JOIN_RULE    => [Role::ROLE_IDS, Role::ROLE_ID]
            ])
            ->prepareInput([
                Change::INPUT_ORDER => [[Change::INPUT_ORDER_FIELD => static::ID, Change::INPUT_ORDER_TYPE => static::ORDER_DESC]]
            ])
            ->input($params)
            ->run()
            ->output();
    }

    public function eventChangeWr(callable $func = null): User
    {
        Change::eventPrepareWr($func ?: function (Change $change) {
            unset($change->whereRule[User::NAME]);
        });
        return $this;
    }

    /**
     * @param $query
     * @return array
     */
    public function indexCount($query)
    {
        return $this->change->doCloneToCountQuery($query);
    }

    // 读 回调
    public function getRoleIdsAttr($value, $data)
    {
        return x_group_concat_to_arr($value);
    }

    // +----------------------------------------------------------------------
    // | 写
    // +----------------------------------------------------------------------

    public function setRoleIdsAttr($value, $data) {
        if (empty($value)) {
            return;
        }
        if ($this->isExists()) {
            // 全量删除 用户-角色
            $this->roles()->detach();
        }
        // 全量新增 用户-角色
        $batch = $this->roles()->saveAll($value);
        if (empty($batch)) {
            x_exception(x_str_f('用户角色关联 全量新增失败'));
        }
    }

    // +----------------------------------------------------------------------
    // | 关联定义
    // +----------------------------------------------------------------------

    /**
     * 获取 所有角色
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, UserRole::class, Role::ROLE_ID, User::USER_ID);
    }
}