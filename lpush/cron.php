<?php

if (php_sapi_name() !== 'cli') {
    exit('not cli run');
}
require_once 'redis_mq.php';
require_once 'order.php';

$redisClient = RedisSingleton::getInstance();
$orderFailArr = [];
$order = new Order();
while (true) {
    $orderFailbuffer = $redisClient->lPop('order_fail');
    if (!$orderFailbuffer) {
        break;
    }
    $orderFailInfo = json_decode($orderFailbuffer, true);
    //判断是否符合通知时间
    if ($orderFailInfo['notify_time'] > time()) {
        $orderFailArr[] = $orderFailbuffer;
        continue;
    }
    $order->repeatCp($orderFailInfo['order_num']);
    // 以避免频繁轮询Redis队列,添加适当的延时
    usleep(100000); // 100毫秒
}
//如果时间没到,再放回队列里面
if (!empty($orderFailArr)) {
    foreach ($orderFailArr as $orderRow) {
        $redisClient->rPush('order_fail', $orderRow);
    }
}