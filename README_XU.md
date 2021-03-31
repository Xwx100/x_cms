## 编辑器选择
```text
phpstorm 2020
插件：Material Theme UI
主题：Atom One Dark(Material)
```

## 跟原框架不同地方 会 加前缀xu.

## 路由采用默认路由

## 使用多应用模式
```bash
composer create-project topthink/think tp6
# 开启 本地服务器
php think run -p 80
# 开启 多应用-方便管理
composer require topthink/think-multi-app
```

## 必须库
```bash
# 查看 tp 已有包
composer search topthink
# 包-数据库迁移
composer require topthink/think-migration

# 验证casbin权限
composer require casbin/think-authz
php think tauthz:publish
php think migrate:run
```

## make
```bash
php make:command
```

## model
```bash
# 1. mysql.config.fields_cache = true 
# 2. cache_key 192.168.56.2:3306@x_admin.x_admin_uri
php think optimize:schema --connection='x_admin'  --table=x_admin.*
```

## 中间件
### 全局中间件
|类名|描述|
|:---|:---|
|App|防前端跨域|
|BloomFilter|防缓存穿透|
|SessionInit|接口是否需要session,可在config.app配置|
|Lock|防并发 防缓存击穿(不放在中间件)|

### 控制器中间件
|类名|描述|
|:---|:---|
|Validate|接口是否需要验证参数|

### swoole - rpc服务
composer require topthink/think-swoole ^3.0
```bash
# 查看帮助
php think list
# 发现rpc服务 并 生成rpc接口
php think rpc:interface
# 加载容器app 服务
php think service:discover
# 开启 rpc 服务
php think swoole:rpc start|stop|restart|reload
```

|name|swoole_name|desc|
|---|---|---|
|swoole.init||框架初始化|
|swoole.start|onStart|启动后在主进程（master）的主线程回调此函数|
|swoole.managerStart|onManagerStart|当管理进程启动时触发此事件|
|swoole.workerStart(app)|onWorkerStart|此事件在 Worker 进程 / Task 进程 启动时发生|
|swoole.task(task)|onTask|在 task 进程内被调用|
|swoole.shutdown|onShutdown|关闭所有进程|