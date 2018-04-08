<?php
return array(
	//'配置项'=>'配置值' 
	'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  'localhost', // 服务器地址
    'DB_NAME'               =>  'kuyou',           // 数据库名
    'DB_USER'               =>  'root',           // 用户名
    'DB_PWD'                =>  '123456',        // 密码
    'DB_PORT'               =>  '3306',          // 端口
    'DB_PREFIX'             =>  'db_',      //数据库表前缀
    'DATA_CACHE_TIME'       =>  60,
    'PAGE_SIZE'             =>  10,         //分页设置 每页page_size个
    'OVERDUE_DAY'             =>  6,
    // 'SPEC_SIZE'             =>  3,          //一个商品的规格限定数
    // 'DB_SQL_BUILD_CACHE'    =>  true,     // 开启SQL解析缓存
    // 'DB_SQL_BUILD_QUEUE'    =>  xcache,     // 设置文件方式
    // 'DB_SQL_BUILD_LENGTH'   =>  20,     // SQL缓存的队列长度
);