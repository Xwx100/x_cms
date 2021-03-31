<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\logic;


use app\common\instance\admin\Model;
use xu\tp6\lock\Lock;


class User extends Base
{

    public function model(array $vars = [], bool $newInstance = true)
    {
        return x_app()->adminModelUser($vars, $newInstance);
    }


    /**
     * @param $name
     * @param $pwd
     * @return \xu\helper\Response
     */
    public function get($name, $pwd)
    {
        try {
            $user = Lock::intance()
                ->injectHandler(app()->cache->store('redis'))
                ->injectFuncArr([[$this, 'user'], [$name, $pwd]])
                ->makeLockPreAdmin('getLock', 'User')
                ->makeLockKeyByFuncArr(1)
                ->run();

            return x_res($this->shield($user));
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());
            return x_res([], $e->getCode() ?: 1, $e->getMessage());
        }
    }


    /**
     * @param $name
     * @param $pwd
     * @return array[]
     */
    public function user($name, $pwd)
    {
        $result = app()->session->all();

        if (!$this->exist($result) || true) {
            $result = $this->model()->eventChangeWr()->get($name, $pwd);
            if ($this->exist($result)) {
                $result[app()->session->getName()] = app()->session->getId();
                app()->session->setData($result);
                app()->cookie->set('uris_front', json_encode($result['uris_front']));
                app()->cookie->set(app()->session->getName(), app()->session->getId());
            }
            else {
                x_exception('用户不存在', 3);
            }
        }

        return $result;
    }

    public function exist(array $user)
    {
        return $user && !empty($user['user']);
    }

    // 屏蔽关键信息
    public function shield(array $user)
    {
        if (!empty($user['user'])) {
            $user['user']['pwd'] = x_var_to_uuid($user['user']);
        }
        return $user;
    }
}