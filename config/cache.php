<?php

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

$redis = [
    'type'       => \xu\tp6\cache\Redis::class,
    'host'       => '192.168.56.2',
    'port'       => 6379,
    'password'   => '',
    'select'     => 0,
    'timeout'    => 0,
    'expire'     => 86400,
    'persistent' => false,
    'prefix'     => '',
    'tag_prefix' => 'tag:',
    'serialize'  => [],
    // xu.
    'tag_md5_no' => true
];

$redisSerialize = $redis;
$redisJson      = array_merge($redis, ['serialize' => ['json_encode', 'json_decode']]);

return [
    // 默认缓存驱动
    'default' => env('cache.driver', 'file'),

    // 缓存连接方式配置
    'stores'  => [
        'file'       => [
            // 驱动方式
            'type'       => 'File',
            // 缓存保存目录
            'path'       => '',
            // 缓存前缀
            'prefix'     => '',
            // 缓存有效期 0表示永久缓存
            'expire'     => 0,
            // 缓存标签前缀
            'tag_prefix' => 'tag:',
            // 序列化机制 例如 ['serialize', 'unserialize']
            'serialize'  => [],
        ],
        // xu.更多的缓存连接
        'redis'      => $redisSerialize,
        'redis_json' => $redisJson
    ],
];
