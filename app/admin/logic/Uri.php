<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\logic;


class Uri extends Base
{
    public function model(array $vars = [], bool $newInstance = true)
    {
        return x_app()->adminModelUri($vars, $newInstance);
    }
}