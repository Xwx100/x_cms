<?php
// +----------------------------------------------------------------------
// | 浏览器携带的session.key 存在 则不刷新
// +----------------------------------------------------------------------

namespace app\common\middleware;



use think\Response;

class SessionInit extends \think\middleware\SessionInit
{
    public function handle($request, \Closure $next)
    {
        // Session初始化
        $varSessionId = $this->app->config->get('session.var_session_id');
        $cookieName   = $this->session->getName();

        if ($varSessionId && $request->request($varSessionId)) {
            $sessionId = $request->request($varSessionId);
        } else {
            $sessionId = $request->cookie($cookieName);
            // xu. url + cookie + header
            if (empty($sessionId)) {
                $sessionId = $request->header($cookieName);
            }
        }

        if ($sessionId) {
            $this->session->setId($sessionId);
        }

        $this->session->init();

        // xu. 是否检查登录
        if (empty($this->session->get('user', []))) {
            $writeUrl = config('app.write_url');
            $enterWrite = false;
            $url = app()->request->url();
            $trimUrl = function ($url){
                return trim(trim($url), '\\/');
            };
            foreach ($writeUrl as $value) {
                if (is_callable($value) && call_user_func_array($value, [$url])) {
                    $enterWrite = true;
                    break;
                } elseif (is_scalar($value) && ($trimUrl($value) === $trimUrl($url))) {
                    $enterWrite = true;
                    break;
                }
            }
            if (!$enterWrite) {
                return json(x_res([], 1, '未登录'));
            }
        }

        $request->withSession($this->session);

        /** @var Response $response */
        $response = $next($request);

        $response->setSession($this->session);

        # 是否设置cookie 交给具体方法
//        if (empty($sessionId) || $this->) {
//            $this->app->cookie->set($cookieName, $this->session->getId());
//        }

        return $response;
    }
}