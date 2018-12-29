<?php

/**
 * @name WxNotify
 * @description 微信异步回调通知
 * @author houzhi
 * @time 2017/11/25 17:15
 */

namespace vApp\lib\src\wx\notify;

use v, vApp;

class WxNotify extends vApp\lib\src\wx\WxBaseStrategy {

    use vApp\lib\NotifyClient;

    protected $config = [];
    protected $data = [];

    public function __construct($conf) {
        $this->config = $conf;
        $params = @file_get_contents('php://input');
        if (empty($params)) {
            return $this->respNotify('数据为空');
        } else {
            $this->data = vApp\lib\Extension::xmlToArray($params);
        }
    }

    public function handle() {
        $mPayment = v\App::model('Payment');
        $flag = !empty($this->data['return_code']) && $this->data['return_code'] == 'SUCCESS' && !empty($this->data['result_code']) && $this->data['result_code'] == 'SUCCESS';
        v\App::log('wx-notify' . json_encode($this->data), 'test.log');
        if (!$flag) {
            return $this->respNotify('状态不正确');
        }
        //校验是否来自支付宝的合法访问，以及验签
        unset($this->data['trade_way']);
        if ($this->data['trade_type'] == 'JSAPI') {
            $signKey = $this->config['public_key'];
            $appId = $this->config['public_app_id'];
        } else {
            $signKey = $this->config['md5_key'];
            $appId = $this->config['app_id'];
        }
        if (empty($this->data['sign']) || !(vApp\lib\Extension::checkSign($this->data, $signKey))) {
            return $this->respNotify('签名不正确');
        }
        $payInfo = $mPayment->getByOutTradeNo($this->data['out_trade_no']);
        if (empty($payInfo)) {
            return $this->respNotify('数据不存在');
        }
        if ($payInfo['total_fee'] != $this->data['total_fee'] && $appId !== $this->data['app_id']) { //校验支付金额是否正确、appid是否为该商户本身
            return $this->respNotify('数据不正确');
        }
        if (empty($payInfo['status'])) { //状态为0表示还没有处理，如果为其他状态则表示已经处理了
            $tradeInfo = [
                'trade_no' => $this->data['transaction_id'],
                'pay_time' => strtotime(strval($this->data['time_end'])),
                'open_id' => $this->data['openid']
            ];
            //返回处理结果
            $result = $mPayment->paySuccess($tradeInfo, $payInfo['_id'], 'wechat');
            if ($result) {
                v\App::log('notify-start', 'test.log');
                $this->notify($payInfo['out_trade_no']); //回调通知客户端支付结果
                v\App::log('notify-end', 'test.log');
                return $this->respNotify('处理成功', true);
            } else {
                return $this->respNotify('处理失败');
            }
        } elseif ($payInfo['status'] == 1) { //已经处理成功了
            return $this->respNotify('处理成功', true);
        }
        return $this->respNotify('处理失败');
    }

    private function respNotify($msg, $flag = false) {
        $data = [
            'return_code' => $flag ? 'SUCCESS' : 'FAIL',
            'return_msg' => $msg,
        ];
        return vApp\lib\Extension::toXml($data);
    }
}