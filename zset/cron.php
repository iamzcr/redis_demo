<?php

if (php_sapi_name() !== 'cli') {
    exit('not cli run');
}
require_once 'redis_mq.php';
require_once 'order.php';

$redisClient = RedisSingleton::getInstance();
$order = new Order();
while (true) {
    $orderSuccessSet = [];
    // 获取当前时间
    $currentTime = time();
    // 获取到期的订单通知
    $orderFailSet = $redisClient->zRangeByScore('order_zset_fail', '-inf', $currentTime);
    if (empty($orderFailSet)) {
        break;
    }
    foreach ($orderFailSet as $orderFailInfo) {
        list($orderData, $num) = explode("order_zset", $orderFailInfo);
        // 处理订单通知
        $orderData = json_decode($orderData, true);
        var_dump($orderData);
        //这里可以获取到通知状态，如果某个订单第一次通知就成功了，把在order_zset_fail集合里面相同的订单取出来，一并移除
        $order->callbackToCp($orderData);
    }
    //移除已处理的通知
    $redisClient->zRem('order_zset_fail', ...$orderFailSet);
    // 以避免频繁轮询Redis队列,添加适当的延时
    usleep(100000); // 100毫秒
}
