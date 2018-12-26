# snowflake
Twitter雪花算法snowflake的PHP实现，解决并发可能造成id重复的问题

#### 概述

- snowflake算法生成的19个字符长度的唯一数字id  
- 由于php-fpm多进程的特性,并发下可能造成id重复,提供redis和文件锁两种方式来控制并发下的id重复,默认使用文件锁形式  
- 生成的id基于时间趋势增长  
- 支持生成时间到2080-09-05   
- 支持32个数据中心,32台机器
- 每毫秒内可以生成4096个不同id  

#### snowflake算法简述

##### 使用64bit来标识一个唯一id，生成规则: 
> 1bit正负标识位 - 41bits毫秒级时间戳 - 5bits数据中心id - 5bits机器id -12bits毫秒内顺序id

##### 使用说明

###### 引用包
```
composer require wantp/snowflake
```
###### 生成id
```
require_once 'vendor/autoload.php';

$IdWorker = \wantp\Snowflake\IdWorker::getIns();
$id = $IdWorker->id();

```

###### 反向解析id

```
$idInfo = $IdWorker->parse($id);
```

###### 分布式，设置机器id

```
$dataCenterId = 2;
$machineId = 5;
$IdWorker = \wantp\Snowflake\IdWorker::getIns($dataCenterId,$machineId);
$id = $IdWorker->id();
```

###### 使用redis来控制并发
- 需要先安装配置好redis，设置redis时需要填写redis配置  
- redis配置说明

| key | 是否必填 | 说明 |  
|:----:|:---:|:---:|  
|host|是|redis主机|
|port|是|redis端口|
|dbIndex|否|redis db index|
|auth|否|redis认证|

```
$resdisConfig = ['host'=>'redis host','port'=>'redis port','dbIndex'=>'redis dbIndex',auth'=>'redis auth'];
$IdWorker = \wantp\Snowflake\IdWorker::getIns()->setRedisConutServer($resdisConfig);
$id = $IdWorker->id();
```