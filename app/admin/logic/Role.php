<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\logic;


class Role extends Base
{
    public function model(array $vars = [], bool $newInstance = true)
    {
        return x_app()->adminModelRole($vars, $newInstance);
    }
}