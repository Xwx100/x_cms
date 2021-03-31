<?php
// +----------------------------------------------------------------------
// | 作用：重新定义原有框架注入实例
// +----------------------------------------------------------------------

use app\ExceptionHandle;
use app\Request;

// 容器Provider定义文件
return [
    'think\Request'          => Request::class,
    'think\exception\Handle' => ExceptionHandle::class,

    \think\Model::class => \app\common\providers\Model::class,
];
