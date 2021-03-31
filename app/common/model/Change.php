<?php
// +----------------------------------------------------------------------
// | fields
// | from 基础表
// | 触发模型join 规则1.字段触发 规则2.join触发
// | where
// | group
// | order
// | having
// +----------------------------------------------------------------------

namespace app\common\model;


use think\db\Query;
use think\facade\Event;
use think\helper\Str;
use xu\helper\Arr;
use xu\traits\Instance;

/**
 * 前端配置 转换成 后端查询类
 * Class Change
 * @package app\common\model
 */
class Change
{
    use Instance;

    public $field = [];
    public $join = [];
    public $where = [];
    public $group = [];
    public $order = [];
    public $having = [];
    public $page = [];
    public $prepareInput = [];

    public $base = '';
    public $baseModel = '';
    public $joinRule = [];
    public $fieldRule = [];
    public $whereRule = [];

    public $closeJr = false;
    public $closeFr = false;
    public $closeWr = false;

    const JR_MODEL_CLASS      = 'model_class';
    const JR_MODEL_INSTANCE   = 'model_instance';
    const JR_MODEL_FIELD      = 'model_field';
    const JR_MODEL_TABLE      = 'model_table';
    const JR_JOIN_RULE        = 'join_rule';
    const JR_JOIN_ON          = 'join_on';
    const JR_JOIN_ON_RULE     = 'join_on_rule';
    const JR_JOIN_TYPE        = 'join_type';
    const JR_JOIN_FIELD_PRE   = 'join_field_pre';
    const FR_SQL              = 'sql';
    const FR_SQL_FORMAT       = 'sql_format';
    const FR_SQL_FORMAT_BY_FR = 'sql_format_by_fr';
    const FR_SQL_SWAP         = 'sql_swap'; // 交换
    const FR_NO_GROUP         = 'no_group';
    const FR_SQL_TABLE        = 'sql_table';
    const WR_METHOD           = 'method';
    const INPUT               = 'input';
    const INPUT_FIELD         = 'field';
    const INPUT_JOIN          = 'join';
    const INPUT_WHERE         = 'where';
    const INPUT_GROUP         = 'group';
    const INPUT_ORDER         = 'order';
    const INPUT_HAVING        = 'having';
    const INPUT_PAGE          = 'page';
    const INPUT_PAGE_SIZE     = 'page_size';
    const INPUT_PAGE_LIMIT    = 'page_limit';

    const INPUT_ORDER_FIELD = 'sort_field';
    const INPUT_ORDER_TYPE  = 'sort_type';

    // !isset() => 覆盖
    public $inputKeys = [
        self::INPUT_FIELD,
        self::INPUT_JOIN,
        self::INPUT_WHERE,
        self::INPUT_GROUP,
        self::INPUT_ORDER,
        self::INPUT_HAVING,
        self::INPUT_PAGE
    ];

    public $afterField = []; // 前端字段 => 带table的数据库字段
    public $afterJoin = [];
    public $afterWhere = [];
    public $afterGroup = [];
    public $afterOrder = [];
    public $afterLimit = [];
    public $afterHaving = [];

    public $limitPage = true;

    /**
     * 记录前端格式
     * @param array $input
     */
    public function input(array $input)
    {
        $f = Arr::get($input, self::INPUT_FIELD, []);
        $w = Arr::get($input, self::INPUT_WHERE, []);
        if ($f && $w) {
            // 防止 field 和 where 字段不一致
            $fWhere                   = array_diff(array_keys(array_filter($w)), $f);
            $input[self::INPUT_FIELD] = array_merge($f, $fWhere);
        }

        foreach ($this->inputKeys as $k) {
            $v = Arr::get($input, $k, []);
            if (empty($v)) {
                $v = Arr::get($this->prepareInput, $k, []);
            }

            if (empty($v)) {
                $this->log(x_str_f('数组键k=%s 数组值为空i=%s', $k, $v));
                continue;
            }
            if (empty(property_exists($this, $k))) {
                $this->log(x_str_f('类不存在属性 k=%s', $k));
                continue;
            }

            $this->$k = $v;
        }

        return $this;
    }


    /**
     * 前端格式 转为 模型格式
     */
    public function run()
    {
        // 处理 join规则
        $this->join()->field()->where()->group()->limit()->order()->having();
        return $this;
    }

    /**
     * 获取 模型query类
     * @return Query
     */
    public function output(): Query
    {
        $query = (new $this->base())->db();

        foreach ($this->afterJoin as $aJ) {
            $query->join(
                $this->jrModelTable($aJ),
                implode(' and ', $this->jrJoinOn($aJ)),
                $this->jrJoinType($aJ)
            );
        }

        if ($this->afterLimit) {
            $query->limit(...$this->afterLimit);
        }

        $this->log(x_str_f('where: %s', array_values($this->afterWhere)));
        $this->log(x_str_f('limit: %s', $this->afterLimit));

        $query
            ->field(array_values($this->afterField))
            ->group($this->afterGroup)
            ->order($this->afterOrder)
            ->where(array_values($this->afterWhere))
            ->having(implode(' and ', $this->afterHaving));

        return $query;
    }

    /**
     * @param Query $query
     * @param array $params
     * @return array
     */
    public function doCloneToCountQuery(Query $query, array $params = []): array
    {
        $this->input($params)->limit();
        if ($this->afterLimit) {
            return $this->cloneToCountQuery($query);
        }
        return [];
    }

    /**
     * @param Query $query
     * @return array
     */
    public function cloneToCountQuery(Query $query): array
    {
        $queryBak = $this->cloneQuery($query);
        $total    = $this->countQuery($queryBak);
        return $this->countToArray($total);
    }

    /**
     * 算总条数
     * @param Query $query
     * @return int
     */
    public function countQuery(Query $query): int
    {
        $query->setOption('limit', null);
        $query->setOption('order', []);
        return $query->count();
    }

    /**
     * @param int $count
     * @param null $page
     * @param null $pageSize
     * @return array
     */
    public function countToArray(int $count, $page = null, $pageSize = null): array
    {
        $page               = [
            'page'      => isset($page) ? $page : $this->limitGetPage(),
            'page_size' => isset($pageSize) ? $pageSize : $this->limitGetPageSize(),
            'total'     => $count, // 总条数
        ];
        $page['total_page'] = ceil($count / $page['page_size']);

        return $page;
    }

    /**
     * 返回新查询器
     * @param Query $query
     * @return Query
     */
    public function cloneQuery(Query $query): Query
    {
        return clone $query;

    }


    /**
     * 录入 join 规则
     * @param array $joinRule
     * @return $this
     */
    public function prepareJr(array $joinRule): Change
    {
        if (empty($this->closeJr)) {
            if (!isset($joinRule[self::JR_MODEL_CLASS]) || !isset($joinRule[self::JR_JOIN_ON_RULE])) {
                return $this;
            }
            $modelClass = $joinRule[self::JR_MODEL_CLASS];
            if (!class_exists($modelClass)) {
                return $this;
            }
            $this->joinRule[$modelClass] = $joinRule;
        }

        return $this;
    }

    public function clearJr() {
        $this->joinRule = [];
    }

    public function clearFr() {
        $this->fieldRule = [];
    }

    /**
     * @param false $closeFr
     * @return Change
     */
    public function closeFr($closeFr = false): Change
    {
        $this->closeFr = $closeFr;
        return $this;
    }

    /**
     * @param false $closeJr
     * @return Change
     */
    public function closeJr($closeJr = false): Change
    {
        $this->closeJr = $closeJr;
        return $this;
    }

    /**
     * @param array $maps ['role_ids' => ['sql' => '数据库原生字段', 'sql_format' => '{%sql} {%sql_alias} 格式化', 'no_group' => 'true-不分组']]
     * @return $this
     */
    public function prepareFr(array $maps): Change
    {
        if (empty($this->closeFr)) {
            $this->fieldRule = $maps;
        }
        return $this;
    }

    /**
     * @param array $whereRule ['name' => ['method' => 'like|自定义匿名函数']]
     * @return $this
     */
    public function prepareWr(array $whereRule): Change
    {
        $this->whereRule = $whereRule;
        event('Change.prepareWr', $this);
        return $this;
    }

    public static function eventPrepareWr(callable $event): \think\Event
    {
        return Event::listen('Change.prepareWr', $event);
    }

    /**
     * @param $base
     * @return $this
     */
    public function prepareBase($base): Change
    {
        $this->base = $base;
        return $this;
    }

    /**
     * @param array $prepareInput
     * @return $this
     */
    public function prepareInput(array $prepareInput): Change
    {
        $this->prepareInput = $prepareInput;
        return $this;
    }

    public function jrModelClass(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_MODEL_CLASS, $v, $merge);
    }

    public function jrModelInstance(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_MODEL_INSTANCE, $v, $merge);
    }

    public function jrModelField(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_MODEL_FIELD, $v, $merge);
    }

    public function jrModelTable(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_MODEL_TABLE, $v, $merge);
    }

    public function jrJoinRule(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_JOIN_RULE, $v, $merge);
    }

    public function jrJoinOn(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_JOIN_ON, $v, $merge);
    }

    public function jrJoinType(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_JOIN_TYPE, $v, $merge);
    }

    public function jrJoinOnRule(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_JOIN_ON_RULE, $v, $merge);
    }

    public function jrJoinFieldPre(&$joinRule, $v = null, $merge = false)
    {
        return $this->makeParams($joinRule, self::JR_JOIN_FIELD_PRE, $v, $merge);
    }

    public function frSqlFormat(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_SQL_FORMAT, $v, $merge);
    }

    public function frSqlFormatByFr(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_SQL_FORMAT_BY_FR, $v, $merge);
    }

    public function frSql(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_SQL, $v, $merge);
    }

    public function frSqlTable(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_SQL_TABLE, $v, $merge);
    }

    public function frSqlSwap(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_SQL_SWAP, $v, $merge);
    }

    public function frNoGroup(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::FR_NO_GROUP, $v, $merge);
    }

    public function wrMethod(&$fieldRule, $v = null, $merge = false)
    {
        return $this->makeParams($fieldRule, self::WR_METHOD, $v, $merge);
    }

    /**
     * @param $joinRule
     * @param $key
     * @param null $v
     * @param false $merge 是否强制覆盖
     * @return mixed
     */
    public function makeParams(&$joinRule, $key, $v = null, $merge = false)
    {
        if ($merge) {
            if (isset($v)) {
                $joinRule[$key] = $v;
            }
        }
        else {
            if (!isset($joinRule[$key]) && isset($v)) {
                $joinRule[$key] = $v;
            }
        }

        return Arr::get($joinRule, $key);
    }

    /**
     * 根据规则 自动join
     */
    public function join(): Change
    {
        $fields = $this->field;
        $join   = [];
        foreach ($this->joinRule as &$jR) {
            $mC = $this->jrModelClass($jR);
            $mI = $this->jrModelInstance($jR, new $mC());
            $mF = $this->jrModelField($jR, array_keys($this->getModelProp($mI)));
            $this->jrModelTable($jR, $mI->getTable());
            $mR = $this->jrJoinRule($jR);
            // 手动匹配：
            $doJoin = false;
            if ($this->join && in_array($mC, $this->join)) {
                $doJoin = true;
            }
            else {
                // 自动匹配：百分百join
                if (empty($mR)) {
                    $doJoin = true;
                }
                else {
                    // 自动匹配：表字段 => join
                    $exist = array_intersect((array)$mR, $fields);
                    if ($exist) {
                        $doJoin = true;
                        // 没匹配到表 放到下次匹配
                        $fields = array_diff($fields, $mF);
                    }
                }
            }

            if ($doJoin) {
                $join[$mC] = $jR;
            }
        }
        unset($jR);

        foreach ($join as &$jR) {
            $mO = $this->jrJoinOnRule($jR);
            $this->jrJoinOn($jR, array_map(function ($item) {
                $t = [];
                foreach ($item as $k => $v) {
                    if ($k === $this->base) {
                        $mT = $this->makeBaseModel()->getTable();
                    }
                    else {
                        $mT = $this->jrModelTable($this->joinRule[$k]);
                    }

                    array_push($t, "{$mT}.{$v}");
                }

                return implode(' = ', $t);
            }, $mO));
            $this->jrToFr($jR);

        }
        $this->afterJoin = $join;

        return $this;
    }

    /**
     * join表 字段 加入 field 规则
     * @param $jR
     */
    public function jrToFr(&$jR)
    {
        // 补全 field 规则
        $mF = $this->jrModelField($jR);
        $mT = $this->jrModelTable($jR);
        foreach ($mF as $f) {
            $cfg = Arr::get($this->fieldRule, $f, []);
            if (!empty($cfg)) {
                // 补全 field 规则 - sql_table 并置换
                $frT    = $this->frSqlTable($cfg);
                $frSql  = $this->frSql($cfg);
                $frSwap = (array)$this->frSqlSwap($cfg);
                if ($frSwap && empty($frT) && $frSql && in_array($frSql, $mF)) {
                    $inputKeys  = (array)Arr::remove($cfg, self::FR_SQL_SWAP);
                    $sqlFormats = (array)Arr::remove($cfg, self::FR_SQL_FORMAT);

                    $this->frSqlTable($cfg, $mT);
                    unset($this->fieldRule[$f]);
                    foreach ($inputKeys as $k => $inputKey) {
                        $cfg[self::FR_SQL_FORMAT]   = $sqlFormats[$k];
                        $this->fieldRule[$inputKey] = $cfg;
                    }
                }
                if (!isset($this->fieldRule[$f])) {
                    $this->fieldRule[$f] = [self::FR_SQL => $f, self::FR_SQL_TABLE => $mT];
                }
            }
            else {
                // 补全所有
                $this->fieldRule[$f] = [self::FR_SQL => $f, self::FR_SQL_TABLE => $mT];
            }
        }
    }

    public function getModelProp(\think\Model $m, string $k = 'schema')
    {
        $c = new \ReflectionObject($m);
        $i = $c->getProperty($k);
        $i->setAccessible(true);
        return $i->getValue($m);
    }

    public function field(): Change
    {
        // 补充基表字段
        $base = [
            self::JR_MODEL_CLASS => $this->base,
            self::JR_MODEL_FIELD => array_keys($this->getModelProp($this->makeBaseModel())),
            self::JR_MODEL_TABLE => $this->makeBaseModel()->getTable()
        ];
        $this->jrToFr($base);

        // 前端字段传空 默认 取FR_NO_GROUP=false的所有字段
        $fs = $this->field ?: array_keys(array_filter($this->fieldRule, function ($fr) {
            $noGroup = $this->frNoGroup($fr);
            return empty($noGroup);
        }));
//        $this->log($fs);

        // 寻找字段
        foreach ($fs as $f) {
            // 防止重复寻找
            if (!empty($this->afterField[$f])) {
                continue;
            }

            // 自动匹配
            $cfg = Arr::get($this->fieldRule, $f);
            if (empty($cfg) || !is_array($cfg)) {
                continue;
            }
            $sql      = $this->frSql($cfg);
            $sqlTable = $this->frSqlTable($cfg);
            if (empty($sqlTable)) {
                continue;
            }
            $aF = "{$sqlTable}.{$sql}";

            // 是否需要格式化 | 重命名
            $sqlFormat = $this->frSqlFormat($cfg);
            if ($sqlFormat) {
                switch ($this->frSqlFormatByFr($cfg)) {
                    case true:
                        // 获取需要格式化字段 todo
                        preg_match_all('/\{\%(.*?)\}/', $sqlFormat, $match);
                        $search  = Arr::get($match, 0);
                        $replace = Arr::get($match, 1);
                        if ($search && $replace) {
                            $replace = array_map(function ($k) {
                                $c           = Arr::get($this->fieldRule, $k);
                                $cModelTable = $this->frSqlTable($c);
                                $cModelSQL   = $this->frSql($c);
                                return implode('.', [$cModelTable, $cModelSQL]);
                            }, $replace);
                            $aF      = str_replace($search, $replace, $sqlFormat);
                        }
                        break;
                    default:
                        // 普通 格式化
                        $aF = str_replace(['{%sql}', '{%sql_alias}'], [$aF, $f], $sqlFormat);
                }
            }
            elseif ($f !== $this->frSql($cfg)) {
                // 判断 是否需要重命名
                $aF = str_replace(['{%sql}', '{%sql_alias}'], [$aF, $f], '{%sql} as {%sql_alias}');
            }

            $this->afterField[$f] = $aF;
        }
//        $this->log($this->afterField);

        return $this;
    }

    public function where(): Change
    {
        if ($this->afterField) {
            foreach ($this->where as $f => $v) {
                $sqlField = Arr::get($this->afterField, $f, '');
                $wR       = Arr::get($this->whereRule, $f, []);
                $wRMethod = $this->wrMethod($wR);
                if (!is_callable($wRMethod)) {
                    $wRMethod = function ($tmp, $v) use ($wRMethod) {
                        $rr = [];

                        // 前端 不忽略 0 '0'
                        if ($tmp && ($v || in_array($v, [0, '0'], true))) {
                            // 可扩展条件方法

                            if ($wRMethod === 'like') {
                                $rr = [$tmp, $wRMethod, "%{$v}%"];
                            } elseif ($wRMethod === 'between') {
                                $rr = [$tmp, $wRMethod, $v];
                            } elseif (empty($wRMethod) && is_scalar($v)) {
                                $rr = [$tmp, '=', $v];
                            } elseif (empty($wRMethod) && is_array($v)) {
                                $rr = [$tmp, 'in', $v];
                            }
                        }

                        return $rr;
                    };
                }

                if ($wRMethod && ($w = call_user_func_array($wRMethod, [$sqlField, $v]))) {
                    $this->afterWhere[$f] = $w;
                }
            }
        }

        return $this;
    }

    public function group(): Change
    {
        if ($this->afterField) {
            foreach ($this->getGroup() as $k => $f) {
                $tmp = Arr::get($this->afterField, $f, '');
                if ($tmp) {
                    $this->afterGroup[$k] = $tmp;
                }
            }
        }
        return $this;
    }

    public function getGroup(): array
    {
        $group = $this->group;
        // 假如没有join 退出
        if (empty($this->afterJoin)) {
            return [];
        }
        if (empty($this->field) || count($this->group) !== count($this->afterField)) {
            // 自动字段 => 自动分组
            $group      = [];
            $existGroup = false;
            foreach ($this->afterField as $f1 => $f2) {
                $cfg     = \think\helper\Arr::get($this->fieldRule, $f1);
                $noGroup = $this->frNoGroup($cfg);
                if (empty($noGroup)) {
                    $group[] = $f1;
                }
                else {
                    $existGroup = true;
                }
            }

            // FR_NO_GROUP 有true 才分组
            if (empty($existGroup)) {
                $group = [];
            }
        }
        return $group;
    }

    public function order(): Change
    {
        if ($this->afterField) {
            foreach ($this->order as $k => $item) {
                $f   = $item['sort_field'];
                $t   = $item['sort_type'];
                $tmp = Arr::get($this->afterField, $f, '');
                if ($tmp) {
                    $this->afterOrder[$k] = "{$tmp} {$t}";
                }
            }
        }
        return $this;
    }

    public function having(): Change
    {
        // todo
        return $this;
    }

    public function limit(): Change
    {
        $pageLimit = $this->limitGetLimit();
        empty($pageLimit) && $this->limitPage();
        if ($this->limitPage) {
            $page     = $this->limitGetPage();
            $pageSize = $this->limitGetPageSize();
            if ($page && $pageSize) {
                $this->afterLimit = [($page - 1) * $pageSize, $pageSize];
            }
        }

        return $this;
    }

    public function limitGetPageSize()
    {
        return Arr::get($this->page, self::INPUT_PAGE_SIZE, 20);
    }

    public function limitGetPage()
    {
        return Arr::get($this->page, self::INPUT_PAGE, 1);
    }

    public function limitGetLimit()
    {
        return Arr::get($this->page, self::INPUT_PAGE_LIMIT, true);
    }

    public function limitPage(): Change
    {
        $this->limitPage = false;
        return $this;
    }

    /**
     * @return \think\Model
     */
    public function makeBaseModel()
    {
        if (empty($this->baseModel)) {
            $this->baseModel = new $this->base();
        }

        return $this->baseModel;
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return [];
    }

    public function log($msg, string $type = 'debug', $limit = 3, $loop = 10)
    {
        x_log_write($msg, $type, $limit, $loop);
    }


}