<?php
// +----------------------------------------------------------------------
// | 接口是否需要验证参数
// +----------------------------------------------------------------------

namespace app\common\middleware;



class Validate
{

    // 验证器 调度
    public function handle($request, \Closure $next) {
        if ($dispatch = x_exist_validate()) {
            $post = input('post.');
            list($class, $action) = $dispatch;
            $instance = validate($class)->scene($action)->failException(false);
            $result = call_user_func_array([$instance, 'check'], [$post]);
            if (empty($result)) {
                return json(x_res([], 1, $instance->getError() ?: '错误'));
            }
        }
        $res = $next($request);
        return $res;
    }
}