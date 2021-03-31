<?php
// +----------------------------------------------------------------------
// | 方便提示 重构代码
// +----------------------------------------------------------------------

namespace app\common\instance;


use xu\traits\Instance;

/**
 * @method \app\admin\controller\User adminControllerUser(array $vars = [], bool $newInstance = false)
 * @method \app\admin\controller\Role adminControllerRole(array $vars = [], bool $newInstance = false)
 * @method \app\admin\controller\Uri adminControllerUri(array $vars = [], bool $newInstance = false)
 *
 * @method \app\admin\logic\User adminLogicUser(array $vars = [], bool $newInstance = false)
 * @method \app\admin\logic\Role adminLogicRole(array $vars = [], bool $newInstance = false)
 * @method \app\admin\logic\Uri adminLogicUri(array $vars = [], bool $newInstance = false)
 *
 * @method \app\admin\model\User adminModelUser(array $vars = [], bool $newInstance = false)
 * @method \app\admin\model\Role adminModelRole(array $vars = [], bool $newInstance = false)
 * @method \app\admin\model\Uri adminModelUri(array $vars = [], bool $newInstance = false)
 * Class App
 * @package app\common\instance
 */
class App
{

    use Instance;

    public $bind = [
        'adminControllerUser' => \app\admin\controller\User::class,
        'adminControllerRole' => \app\admin\controller\Role::class,
        'adminControllerUri'  => \app\admin\controller\Uri::class,

        'adminLogicUser' => \app\admin\logic\User::class,
        'adminLogicRole' => \app\admin\logic\Role::class,
        'adminLogicUri'  => \app\admin\logic\Uri::class,

        'adminModelUser' => \app\admin\model\User::class,
        'adminModelRole' => \app\admin\model\Role::class,
        'adminModelUri'  => \app\admin\model\Uri::class,
    ];

    public function __call($name, $arguments)
    {
        // 后续文件太大 可以拆分 todo
        return app()->make($this->bind[$name] ?? $name, ...$arguments);
    }
}
