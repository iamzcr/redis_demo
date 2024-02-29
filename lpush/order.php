<?php

require_once 'redis_mq.php';


class Order
{
    private $redisClient;

    public function __construct()
    {
        $this->redisClient = RedisSingleton::getInstance();
    }

    public function callbackToCp($orderData = [])
    {

        //这里订单记录可以放到mysql,为了demo方便,直接把订单存到redis
        $this->redisClient->set($orderData['order_num'], json_encode($orderData));
        $orderInfo = $this->redisClient->get($orderData['order_num']);
        $orderInfo = json_decode($orderInfo, 1);
        if (!empty($orderInfo['is_callback'])) {
            return true;
        }

        //研发方响应成功
        if ($this->curlPost($orderInfo)) {
            //TODO 通知成功，处理修改订单状态逻辑is_callback=1
            return true;
        }
        //研发方响应失败，把失败订单写到队列里面，用于后续定时任务出列重复通知，通知5次
        if ($orderInfo['callback_count'] < 5) {
            //重新发放redis队列
            $orderMq = [
                'order_num' => $orderInfo['order_num'],
                //按通知次数*2*60秒人列
                'notify_time' => time() + 60 * ($orderInfo['callback_count'] * 2),
            ];
            $this->redisClient->lpush('order_fail', json_encode($orderMq));
        }
        return false;
    }

    public function notifyCp($orderData)
    {
        $isCallback = $this->callbackToCp($orderData);
        $orderData['is_callback'] = intval($isCallback);
        $orderData['callback_count'] = $orderData['callback_count'] + 1;
        file_put_contents('/tmp/order.log', json_encode($orderData) . PHP_EOL, FILE_APPEND);
        $this->redisClient->set($orderData['order_num'], json_encode($orderData));
    }

    private function curlPost($orderData)
    {
        //TODO 具体发送http请求逻辑
        return false;
    }

    //重复通知
    public function repeatCp($orderNum)
    {
        $orderInfo = $this->redisClient->get($orderNum);
        $orderInfo = json_decode($orderInfo, 1);
        $this->notifyCp($orderInfo);
    }
}




