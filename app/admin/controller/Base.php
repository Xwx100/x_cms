<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\controller;


use app\common\controller\Vl;

use app\admin\logic\Role;
use app\admin\logic\User;
use app\admin\logic\Uri;


abstract class Base extends Vl
{
    /**
     * 自己定义，方便跟踪
     * @return User|Role|Uri
     */
    abstract public function logic();

    public function index()
    {
        $params = input('post.');

        return json($this->logic()->index($params))->options();
    }

    public function create()
    {
        $params = input('post.');

        return json($this->logic()->create($params));
    }

    public function update()
    {
        $params = input('post.');

        return json($this->logic()->update($params));
    }

    public function del()
    {
        $params = input('post.');

        return json($this->logic()->del($params));
    }
}