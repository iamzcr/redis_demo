<?php

require_once 'redis_mq.php';


class Order
{
    private $redisClient;

    public function __construct()
    {
        $this->redisClient = RedisSingleton::getInstance();
    }

    public function callbackToCp($orderData)
    {
        file_put_contents('/tmp/order_set.log', json_encode($orderData) . PHP_EOL, FILE_APPEND);
        //这里订单记录可以放到mysql,为了demo方便,直接把订单存到redis
        $this->redisClient->set($orderData['order_num'], json_encode($orderData));
        $orderInfo = $this->redisClient->get($orderData['order_num']);
        $orderInfo = json_decode($orderInfo, 1);
        if (empty($orderInfo['is_callback'])) {
            return false;
        }
        if (!$this->curlPost($orderInfo)) {
            return false;
        }
        //TODO 通知成功，处理修改订单状态逻辑is_callback=1
        $orderData['is_callback'] = 1;
        $this->redisClient->set($orderData['order_num'], json_encode($orderData));
        return true;
    }

    public function notifyCp($orderData)
    {
        $res = $this->callbackToCp($orderData);
        //研发方响应失败，把失败订单写到有序集合里面，用于后续定时任务出列重复通知，通知5次
        if (!$res) {
            for ($i = 1; $i <= 5; $i++) {
                //有序集合中的成员必须是唯一的。如果尝试插入一个已经存在的成员，它的分数将被更新，但返回值将是 0，表示插入失败,所以第三个参数需要做一下处理
                $this->redisClient->zAdd('order_zset_fail', time() + $i * 10, json_encode($orderData) . 'order_zset' . $i);
            }
        }
    }
    private function curlPost($orderData)
    {
        //TODO 具体发送http请求逻辑
        return false;
    }

}




