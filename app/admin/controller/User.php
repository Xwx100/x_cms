<?php
declare (strict_types=1);

namespace app\admin\controller;



use xu\tp6\services\xu_service\Xu;

class User extends Base
{
    public function logic()
    {
        return x_app()->adminLogicUser();
    }

    public function get()
    {
        $name = input('post.name', '');
        $pwd  = input('post.pwd', '');

        return json($this->logic()->get($name, $pwd));
    }

    public function logout()
    {
        app()->cookie->delete(app()->session->getName());
        app()->session->clear();

        return json(x_res());
    }

    public function test() {
        x_log_write('2222222222222');
        /**
         * @var Xu $x
         */
        $x = app(Xu::class);
        ($x)->xxTypeSetRpc();
        $x->xxRequestIdMake('1111111');
        x_log_write('444');
        x_log_write('55');
        var_dump(json_encode($x));
    }


}
