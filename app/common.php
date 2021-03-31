<?php
// 应用公共文件
if (!function_exists('x_user')) {
    function x_user() {
        return app()->session->get('user.name', 'admin');
    }
}

if (!function_exists('x_app')) {
    function x_app() {
        return \app\common\instance\App::intance();
    }
}