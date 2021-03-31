<?php
// +----------------------------------------------------------------------
// | Date:   2020/12/28 0028
// +----------------------------------------------------------------------
// | Author: Administrator
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\Change;
use xu\helper\Arr;

class Uri extends Base
{

    // 配置 表字段常量（数据库）
    const URI_ID     = 'uri_id';
    const URI        = 'uri';
    const LEVEL      = 'level';
    const FRONT_ITEM = 'front_item';

    // 配置 表字段常量（前端）
    const URI_IDS = 'uri_ids';

    // 模型对应数据表字段及类型
    protected $schema = [
        self::ID         => 'integer',
        self::NAME       => 'string',
        self::URI        => 'string',
        self::URI_ID     => 'integer',
        self::LEVEL      => 'level',
        self::FRONT_ITEM => 'front_item',
        self::CREATE_AT  => 'string',
        self::UPDATE_AT  => 'string',
        self::CREATE_BY  => 'string',
        self::UPDATE_BY  => 'string',
    ];
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    // 开启自动写入用户字段
    protected $autoWriteUser = true;

    // 配置 模型自动转换的字段及类型
    protected $type = [
        self::CREATE_AT  => 'datetime',
        self::UPDATE_AT  => 'datetime',
        self::FRONT_ITEM => 'json'
    ];

    // +----------------------------------------------------------------------
    // | 读
    // +----------------------------------------------------------------------
    public function findFront($ids, &$record = [], $childKey = 'parent', $limit = 3)
    {
        $rows     = [];
        $bakLimit = $limit;

        if (empty($ids)) {
            return $rows;
        }
        do {
            // 当前信息
            $rows = $this->indexChange([
                Change::INPUT_WHERE => [
                    self::ID => $ids
                ],
                Change::INPUT_PAGE  => [
                    Change::INPUT_PAGE_LIMIT => false
                ]
            ])->selectOrEmptyArray();
            // 父信息
            $parents   = [];
            $parentIds = array_filter(array_unique(array_column($rows, self::URI_ID)));
            if ($parentIds) {
                $parents = $this->findFront($parentIds, $record, $childKey, $bakLimit);
            }
            $rows = array_merge($rows, $parents);

            $limit--;
        } while ($limit >= 0 && $parentIds);

        return $rows;
    }

    public function findCurLevel($uriId, $level = 1, $limit = 3)
    {
        $bakLimit = $limit;
        if (empty($uriId)) {
            return $level;
        }
        do {
            $row = $this->indexChange([
                Change::INPUT_WHERE => [
                    self::ID => $uriId
                ]
            ])->findOrEmpty();
            if (!$row->isEmpty()) {
                $level = $this->findCurLevel($row[self::URI_ID], $level + 1, $bakLimit);
            }
            $limit--;
        } while ($limit >= 0 && $level > 3);

        return $level;
    }

    public function findBack($where, &$record, &$recordLimit, $limit = 3)
    {
        $bakLimit = $limit;
        do {
            $rows = $this->indexChange([
                Change::INPUT_WHERE => $where
            ])->select();
            foreach ($rows as &$row) {
                $recordLimit[] = $row[self::URI];
                if ($row[self::LEVEL] < $bakLimit) {

                    $row['children'] = $this->findBack([
                        self::URI_ID => $row[self::ID]
                    ], $record, $recordLimit, $bakLimit);
                }
                else {

                    if ($row[self::LEVEL] == $bakLimit) {
                        $record[] = implode('/', $recordLimit);
                    }
                    $row['children'] = [];
                }
            }
            $limit--;
        } while ($limit >= 0 && !$rows->isEmpty());

        return $rows;
    }

    /**
     * 获取 最后 等级
     * @param array $arr
     * @param array $record
     * @param string $childKey
     * @param string $recordKey
     * @return array
     */
    public function getLastUris(array $arr, $record = [], $childKey = 'children', $recordKey = self::URI)
    {
        $all = [];
        foreach ($arr as $item) {
            if (!empty($item[$childKey])) {
                $t   = $this::getLastUris($item[$childKey], array_merge($record, [$item[$recordKey]]), $childKey, $recordKey);
                $all = array_merge($all, $t);
            }
            else {
                $c     = array_merge($record, [$item[$recordKey]]);
                $all[] = implode('/', $c);
            }
        }
        return $all;
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

    public function indexChange(array $params, $closeFr = false, $closeJr = false)
    {
        $this->change(Change::renewInstance()
            ->prepareBase(static::class)
            ->closeFr($closeFr)
            ->closeJr($closeJr));

        return $this->change
            ->prepareBase(static::class)
            ->prepareFr(
                [
                    UserRole::ROLE_ID => [
                        Change::FR_SQL      => UserRole::ROLE_ID, Change::FR_SQL_FORMAT => 'group_concat({%sql}) as {%sql_alias}',
                        Change::FR_NO_GROUP => true, Change::FR_SQL_SWAP => UserRole::ROLE_IDS
                    ]
                ]
            )
            ->prepareWr([
                User::NAME      => ['method' => 'like'],
                USER::CREATE_AT => ['method' => 'between'],
                USER::UPDATE_AT => ['method' => 'between'],
            ])
            ->prepareJr([
                Change::JR_MODEL_CLASS  => RolePermission::class,
                Change::JR_JOIN_ON_RULE => [
                    [static::class => static::ID, RolePermission::class => RolePermission::PERMISSION_ID],
                ],
                Change::JR_JOIN_TYPE    => RolePermission::LEFT_JOIN,
                Change::JR_JOIN_RULE    => [Role::ROLE_ID, Role::ROLE_IDS]
            ])
            ->prepareInput([
                //                Change::INPUT_FIELD => [static::ID, UserRole::ROLE_IDS],
                //                Change::INPUT_GROUP => [static::ID],
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

    public function getRoleIdsAttr($value, $data)
    {
        return x_group_concat_to_arr($value);
    }

    public function setLevelAttr($value, $data)
    {
        if (empty($value) && ($uriId = Arr::get($data, 'uri_id', ''))) {
            return $this->findCurLevel($uriId);
        }
        return $value;
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
        return $this->belongsToMany(Role::class, UserRole::class);
    }
}