<?php
// +----------------------------------------------------------------------
// | 全局应用中间件
// +----------------------------------------------------------------------

namespace app\common\middleware;



class App
{

    public function handle($request, \Closure $next) {
        $res = $next($request);
        $res->header([
            'Access-Control-Allow-Origin'      => 'http://localhost:9528',
            'Access-Control-Allow-Headers'     => 'Origin,Content-Type,Accept,token,X-Requested-With',
            'Access-Control-Allow-Methods'     => 'POST,GET',
            'Access-Control-Allow-Credentials' => 'true'
        ]);
        return $res;
    }
}