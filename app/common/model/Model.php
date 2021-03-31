<?php
// +----------------------------------------------------------------------
// | 全库通用属性
// +----------------------------------------------------------------------

namespace app\common\model;


interface Model
{
    // 通用属性
    const ID = 'id';
    const NAME = 'name';
    const CREATE_AT = 'create_at';
    const UPDATE_AT = 'update_at';

    const CREATE_BY = 'create_by';
    const UPDATE_BY = 'update_by';

    const ORDER_DESC = 'desc';
    const ORDER_ASC = 'asc';

    const INNER_JOIN = 'inner';
    const LEFT_JOIN = 'left';
    const RIGHT_JOIN = 'right';
}