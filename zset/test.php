<?php
require_once 'order.php';
//模拟要回调给研发方的订单
$orderData = [
    'order_num' => 'O' . strval(time()),
    'uid' => rand(10000, 999999),
    'is_callback' => 0,
    'callback_count' => 0, //通知次数
    'create_time' => time(),
];
(new Order())->notifyCp($orderData);


//$redisClient = RedisSingleton::getInstance();
////$values = $redisClient->lrange('order_fail', 0, -1);
////// 打印所有值
////foreach ($values as $value) {
////    echo $value . PHP_EOL;
////}
//$redisClient->del('order_fail');
//$values = $redisClient->lrange('order_fail', 0, -1);
//// 打印所有值
//foreach ($values as $value) {
//    echo $value . PHP_EOL;
//}
//// 清空当前数据库
//$redisClient->flushDB();