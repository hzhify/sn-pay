<?php

/**
 * @name BaiduNotify
 * @description 百度异步回调通知
 * @author houzhi
 * @time 2018/10/18 17:15
 */

namespace vApp\lib\src\baidu\notify;

use v, vApp;

class BaiduNotify extends vApp\lib\src\baidu\BaiduBaseStrategy {
    use vApp\lib\NotifyClient;

    protected $config = [];
    protected $data = [];

    public function __construct($conf) {
        $this->config = $conf;
        $this->data = v\App::param();
        if (empty($this->data)) {
            return $this->respNotify('fail');
        }
    }

    public function handle() {
        $mPayment = v\App::model('Payment');
        if (!(isset($this->data['status']) && $this->data['status'] == 2)) { //支付成功
            return $this->respNotify('status not right');
        }

        if (empty($this->data['rsaSign'])) {
            return $this->respNotify('sign empty');
        }
        //校验签名
        unset($this->data['trade_way'], $this->data['mch']);
        if (!$this->check($this->data)) {
            return $this->respNotify('sign error');
        }
        $payInfo = $mPayment->getByOutTradeNo($this->data['tpOrderId']);
        if (empty($payInfo)) {
            return $this->respNotify('order error');
        }
        //校验支付金额是否正确、dealId是否为该商户本身
        if ($payInfo['total_fee'] != $this->data['totalMoney'] && $this->config['deal_id'] !== $this->data['dealId']) {
            return $this->respNotify('params error');
        }
        if (empty($payInfo['status'])) { //状态为0表示还没有处理，如果为其他状态则表示已经处理了
            $tradeInfo = [
                'trade_no' => $this->data['orderId'],
                'pay_time' => $this->data['payTime'],
                'buyer_id' => $this->data['userId'] ?? '',
                'notify_id' => empty($this->data['notify_id']) ? '' : $this->data['notify_id'],
            ];
            //返回处理结果
            $result = $mPayment->paySuccess($tradeInfo, $payInfo['_id'], 'baidu');
            if ($result) {
                $this->notify($payInfo['out_trade_no']); //异步通知客户端支付结果
            }
            return $this->respNotify($result ? 'success' : 'fail');
        } elseif ($payInfo['status'] == 1) { //已经处理成功了
            return $this->respNotify();
        }
        return $this->respNotify('fail');
    }

    private function respNotify($msg = 'success') {
        $data = [
            'errno' => $msg === 'success' ? 0 : 1,
            'msg' => $msg,
            'data' => ['isConsumed' => $msg === 'success' ? 2 : 1]
        ];
        return json_encode($data);
    }
}