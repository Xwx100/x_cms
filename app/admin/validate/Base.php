<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\validate;


use think\Validate;
use \app\admin\model\Base as ModBase;

class Base extends Validate
{
    protected $rule = [
        ModBase::ID   => ['require'],
        ModBase::NAME => ['require', 'max:50'],
    ];

    protected $scene = [
        'index'  => ['no'],
        'create' => [ModBase::NAME],
        'update' => [ModBase::ID],
        'del'    => [ModBase::ID],
    ];
}