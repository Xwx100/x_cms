<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\controller;


class Uri extends Base
{
    public function logic()
    {
        return x_app()->adminLogicUri();
    }
}