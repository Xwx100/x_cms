<?php
// +----------------------------------------------------------------------
// |
// +----------------------------------------------------------------------

namespace app\admin\logic;


use app\admin\model\User;
use app\admin\model\Role;
use app\admin\model\Uri;
use xu\helper\Response;

abstract class Base
{
    /**
     * 便于跟踪
     * @return User|Role|Uri
     */
    abstract public function model();

    /**
     * @param array $params
     * @return Response
     */
    public function index(array $params)
    {
        $mod = $this->model();

        $modRes = $mod->index($params);

        return x_res($modRes);
    }

    /**
     * @param array $params
     * @return Response
     */
    public function create(array $params)
    {
        $mod = $this->model();

        try {
            $mod->transaction(function () use ($mod, $params) {
                $mod->exists(false)->save($params);
                if (empty($mod->getKey())) {
                    x_exception(x_str_f('创建 失败'));
                }
            });
        } catch (\Exception $e) {
            return x_res([], 1, $e->getMessage());
        }

        return x_res($mod);
    }

    /**
     * @param array $params
     * @return Response
     */
    public function update(array $params)
    {
        $mod = $this->model();

        try {
            $mod->transaction(function () use ($mod, $params) {
                $mod->exists(true)->save($params);
                if (empty($mod->getKey())) {
                    x_exception(x_str_f('修改 失败'));
                }
            });
        } catch (\Exception $e) {
            return x_res([], 1, $e->getMessage());
        }

        return x_res($mod);
    }

    /**
     * @param array $params
     * @return Response
     */
    public function del(array $params)
    {
        $mod = $this->model();

        $batch = $mod->where(['id' => $params['id']])->delete();
        if (empty($batch)) {
            return x_res([], 1, '删除失败');
        }

        return x_res();
    }
}