<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\controller;


class Role extends Base
{

    public function logic()
    {
        return x_app()->adminLogicRole();
    }
}