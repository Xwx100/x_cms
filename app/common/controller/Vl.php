<?php
// +----------------------------------------------------------------------
// | 验证器中间件：validate
// +----------------------------------------------------------------------

namespace app\common\controller;


use app\BaseController;
use app\common\middleware\Validate;

class Vl extends BaseController
{
    // 数组顺序 即是 控制器中间件顺序
    public $middleware = [Validate::class];
}