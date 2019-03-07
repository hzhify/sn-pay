<?php

/**
 * @name Query
 * @description 微信退款查询
 * @author hz
 * @time 2019/2/22 17:00
 */

namespace pay\wx\refund;

use pay\wx\WxBaseStrategy;
use pay\util\Func;

class Query extends WxBaseStrategy {

    protected $gatewayUrl = 'https://api.mch.weixin.qq.com/pay/refundquery';

    public function execute() {
        $fields = 'out_trade_no,trade_no,out_refund_no,pay_type';
        if (Func::validParams($this->data, $fields)) {
            $this->data['transaction_id'] = $this->data['trade_no'];
            $this->isPubPay = $this->data['pay_type'] === 'pub';
            $this->data = Func::arrayFilterKey($this->data, 'transaction_id,out_trade_no,out_refund_no');
            $this->data['appid'] = $this->config['app_id'];
            $this->data['mch_id'] = $this->config['mch_id'];
            if ($this->isPubPay) {
                $this->data['appid'] = $this->config['public_app_id'];
                $this->data['mch_id'] = $this->config['public_mch_id'];
            }
            return $this->clientRequestExecute($this->data);
        }
        return false;
    }

    public function aopClientRequestExecuteCallback($result) {
        return [
            'out_biz_no' => $result['refund_id_0'],
            'total_fee'  => $result['refund_fee_0'],
        ];
    }
}