<?php
// +----------------------------------------------------------------------
// | 调度
// +----------------------------------------------------------------------

namespace app\common\instance;

/**
 * 调用方式:
 * @property string $name
 * @property Object $dispatcher
 * @property array $bind
 * Trait Dispatcher
 * @package app\common\instance
 */
trait Dispatcher
{

    /**
     * 设置当前实例
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name       = $name;
        $this->dispatcher = $this->{$this->name};
        return $this;
    }

    /**
     * 获取实例
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return app()->make($this->bind[$name] ?? $name);
    }

    /**
     * 调用当前实例方法
     * @param $name
     * @param $arguments
     * @return false|mixed
     * @throws \Exception
     */
    public function __call($name, $arguments): bool
    {
        if (empty($this->dispatcher)) {
            x_exception('调度器 未设置名字=%s, 请先调用setName', $this->name);
        }
        return call_user_func_array([$this->dispatcher, $name], $arguments);
    }
}