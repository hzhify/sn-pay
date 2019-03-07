<?php

/**
 * @name Refund
 * @description 微信退款
 * @author hz
 * @time 2019/2/22 17:00
 */

namespace pay\wx\refund;

use pay\wx\WxBaseStrategy;
use pay\util\Func;

class Refund extends WxBaseStrategy {

    protected $gatewayUrl = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

    public function execute() {
        [
            'status' => 0,
            'msg'    => '',
            'data'   => []
        ];
        if (Func::validParams($this->data, 'out_trade_no,trade_no,total_fee,out_refund_no,original_fee,remark,notify_url')) {
            $renameFields = [
                'trade_no'     => 'transaction_id',
                'total_fee'    => 'refund_fee',
                'original_fee' => 'total_fee',
                'remark'       => 'refund_desc',
            ];
            Func::arrayReKey($this->data, $renameFields);
            $this->isPubPay = $this->data['pay_type'] === 'pub';
            $this->data = Func::arrayFilterKey($this->data, 'transaction_id,out_trade_no,out_refund_no,total_fee,refund_fee,refund_desc');
            $this->data['appid'] = $this->config['app_id'];
            $this->data['mch_id'] = $this->config['mch_id'];
            $certs = [
                'cert' => $this->config['ssl_cert_path'],
                'key'  => $this->config['ssl_key_path'],
            ];
            if ($this->isPubPay) {
                $this->data['appid'] = $this->config['public_app_id'];
                $this->data['mch_id'] = $this->config['public_mch_id'];
                $certs = [
                    'cert' => $this->config['pub_ssl_cert_path'],
                    'key'  => $this->config['pub_ssl_key_path'],
                ];
            }
            return $this->clientRequestExecute($this->data, $certs);
        }
        return false;
    }

    public function aopClientRequestExecuteCallback($result) {
        return [
            'out_biz_no' => $result['refund_id'],
            'total_fee'  => $result['refund_fee'],
        ];
    }
}