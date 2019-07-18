<?php

/**
 * @name Refund
 * @description 微信退款异步回调通知
 * @author houzhi
 * @time 2019/2/22 17:30
 */

namespace pay\wx\notify;

use pay\util\Func;
use pay\wx\WxBaseStrategy;

class Refund extends WxBaseStrategy {

    protected $checkSign = true;
    
    public function execute() {
        if (empty($this->data['pay_type'])) {
            $this->err->add('参数不正确');
            return false;
        }
        $params = @file_get_contents('php://input');
        if (empty($params)) {
            $this->err->add('数据为空');
            return false;
        } else {
            $params = Func::xmlToArray($params);
        }
        Func::log('wx-refund-notify-execute-start-data-' . json_encode($params), $this->logFile);
        $flag = !empty($params['return_code']) && $params['return_code'] == 'SUCCESS' && !empty($params['result_code']) && $params['result_code'] == 'SUCCESS';
        if (!$flag) {
            Func::log('wx-refund-notify-execute-status-error', $this->logFile);
            $this->err->add('状态不正确');
            return false;
        }

        // 验签
        unset($params['trade_way']);
        $signKey = $this->config['md5_key'];
        $appId = $this->config['app_id'];

        if ($this->data['pay_type'] === 'pub') {
            $signKey = $this->config['public_key'];
            $appId = $this->config['public_app_id'];
        }

        if (empty($params['sign']) || !(Func::checkSign($params, $signKey))) {
            Func::log('wx-refund-notify-execute-sign-error', $this->logFile);
            $this->err->add('签名不正确');
            return false;
        }

        if ($appId !== $params['appid']) { //校验appid是否为该商户本身
            Func::log('wx-refund-notify-execute-appid-error', $this->logFile);
            $this->err->add('数据不正确');
            return false;
        }

        return [
            'trade_no'      => $params['transaction_id'],
            'out_trade_no'  => $params['out_trade_no'],
            'out_refund_no' => $params['out_refund_no'],
            'out_biz_no'    => $params['refund_id'],
            'pay_time'      => strtotime(strval($params['success_time'])),
            'total_fee'     => $params['total_fee'],
        ];
    }
}