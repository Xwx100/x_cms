<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\model;


use app\common\model\Change;
use app\common\model\XAdmin;
use think\db\Query;
use think\Model;
use xu\traits\Instance;

/**
 * Class Base
 * @mixin \xu\tp6\model\Model
 * @package app\admin\model
 */
class Base extends Model implements XAdmin
{
    use Instance, \xu\tp6\model\Model;

    // 自动时间戳
    protected $connection = self::X_ADMIN;
    protected $createTime = self::CREATE_AT;
    protected $updateTime = self::UPDATE_AT;

    /**
     * @var Change $change
     */
    public $change = null;

    public function change(Change $change = null) {
        if (is_null($this->change) && is_null($change)) {
            $this->change = Change::renewInstance();
        } elseif (is_object($change)) {
            $this->change = $change;
        }

        return $this;
    }

    /**
     * @param array $params
     * @param bool $closeFr
     * @param bool $closeJr
     * @return Query
     */
    public function indexChange(array $params, $closeFr = false, $closeJr = false) {

    }


    /**
     * @param $ids
     * @return Query
     */
    public function getRowsByIds($ids) {
        return $this->indexChange([
            Change::INPUT_FIELD => array_keys($this->schema),
            Change::INPUT_WHERE => [
                self::ID => $ids
            ]
        ], true, true);
    }
}