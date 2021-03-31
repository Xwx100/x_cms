<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\validate;


use app\admin\model\Base as ModBase;
use app\admin\model\User as ModUser;

class User extends Base
{
    protected $rule = [
        ModBase::ID   => ['require'],
        ModBase::NAME => ['require', 'max:50'],
        ModUser::PWD  => ['require', 'alphaNum', 'length:6,20'],
    ];

    protected $scene = [
        'index'  => ['no'],
        'create' => [ModBase::NAME],
        'update' => [ModBase::ID],
        'del'    => [ModBase::ID],
        'get'    => [ModUser::NAME, ModUser::PWD],
        'logout'  => ['no'],
        'test'  => ['no'],
    ];
}