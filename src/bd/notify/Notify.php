<?php

/**
 * @name Notify
 * @description 百度异步回调通知
 * @author houzhi
 * @time 2018/10/18 17:15
 */

namespace pay\bd\notify;

use pay\bd\BdBaseStrategy;

class Notify extends BdBaseStrategy
{
    public function execute()
    {
        if (empty($this->data['rsaSign'])) {
            $this->err->add('sign empty');
            return false;
        }
        //校验签名
        unset($this->data['trade_way'], $this->data['mch']);
        if (!$this->checkSign($this->data)) {
            $this->err->add('sign error');
            return false;
        }
        //dealId是否为该商户本身
        if ($this->config['deal_id'] !== $this->data['dealId']) {
            $this->err->add('params error');
            return false;
        }
        return [
            'out_trade_no' => $this->data['tpOrderId'],
            'trade_no'     => $this->data['orderId'],
            'pay_time'     => $this->data['payTime'],
            'buyer_id'     => $this->data['userId'] ?? '',
            'notify_id'    => empty($this->data['notify_id']) ? '' : $this->data['notify_id'],
        ];
    }
}