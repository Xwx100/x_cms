<?php
// +----------------------------------------------------------------------
// | 防止缓存穿透(缓存+数据库不存在) 4s内全部过期
// +----------------------------------------------------------------------

namespace app\common\middleware;


use think\Request;
use think\Response;
use xu\tp6\bloom_filter\BloomFilter as bf;

class BloomFilter
{

    public function handle(Request $request, \Closure $next)
    {
        if (empty(x_is_api())) {
            return $next($request);
        }

        $url       = $request->url();
        $params    = array_merge($request->param(), $request->post());
        $target    = [$url, $params];
        $bf        = bf::intance()->makeBucket($url)->makeTarget($target);
        $bfDataKey = $bf->bucket . $bf->target;

        if ($bf->exists()) {
            x_log_write('命中布隆过滤器');
            $data = $bf->redis->get($bfDataKey, []);
            if (empty($data)) {
                $data = x_res([]);
            }
            return json($data);
        }

        /**
         * @var Response $response
         */
        $response = $next($request);
        $data     = $response->getData();

        if (x_is_api() && (empty($data) || empty($data['data']))) {
            $bf->add();
            $bf->redis->set($bfDataKey, is_object($data) ? $data->toArray() : $data, $bf->redisExpire + rand(2, 6));
        }

        return $response;
    }
}