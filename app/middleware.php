<?php
// 全局中间件定义文件

// +----------------------------------------------------------------------
// | 作用：解耦 多层处理数据
// | xu.嵌套中间件 全局中间件->应用中间件->路由中间件->控制器中间件
// | xu.前置行为 先进先出
// | xu.后置行为 后进先出
// | xu.eg 全局中间件a、b 路由中间件c a_before->b_before->c_before->c_after->b_after->a_after
// +----------------------------------------------------------------------


return [
    // 全局请求缓存
    // \think\middleware\CheckRequestCache::class,
    // 多语言加载
    // \think\middleware\LoadLangPack::class,
    // Session初始化
    \app\common\middleware\App::class,
    \app\common\middleware\BloomFilter::class,
    \app\common\middleware\SessionInit::class
];
