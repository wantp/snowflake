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

$client = \wantp\Snowflake\Client::getIns();
$id = $uuid->id();


```

###### 反向解析id

```
$idInfo = $uuid->parse($id);
```

###### 分布式，设置机器id

```
require_once 'vendor/autoload.php';

$uuid = \Snowflake\Uuid::getInstance(10,2);
$id = $uuid->generate();
```

###### 使用redis来控制并发

```
require_once 'vendor/autoload.php';

$uuid = \Snowflake\Uuid::getInstance(10,2,2,['host'=>'redis.host','port'=>'6379','auth'=>'your redis auth']);
$id = $uuid->generate();
```