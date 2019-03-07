<?php

/**
 * @name Qr
 * @description 微信扫码支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;

use pay\util\Func;

class Qr extends WxPayBaseStrategy {
    protected $extValidFields = ['product_id'];

    protected function setTradeType() {
        $this->data['trade_type'] = 'NATIVE';
    }

    public function aopClientRequestExecuteCallback($result) {
        $qrCodeDir = $this->data['qr_code_dir'] ?? '';
        $return = [
            'code_img' => Func::getQrCode($result['code_url'], 'wx_' . $this->data['out_trade_no'], $qrCodeDir),
            'code_url' => $result['code_url']
        ];
        return $return;
    }
}