<?php

/**
 * @name WxQrPay
 * @description 微信扫码支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace vApp\lib\src\wx\pay;

use v, vApp;

class WxQrPay extends WxPayBaseStrategy {

    public function validData() {
        parent::validData();
        if (empty($this->data['product_id'])) {
            v\Err::add('产品ID不能为空', 'product_id');
            return false;
        }
        return true;
    }

    protected function setTradeType() {
        $this->data['trade_type'] = 'NATIVE';
    }


    public function handle() {
        $result = parent::handle();
        if ($result) {
            $qrCode = vApp\lib\Extension::getQrcode($result['code_url'], 'wechat_'.$this->data['out_trade_no'], 'wechat');
            $return = [
                'code_img_url' => $this->config['host'] . v\App::url('static/temp/qrcode/' . date("Y-m-d") . '/' . $qrCode),
                'code_url' => $result['code_url']
            ];
            return $return;
        }
        return false;
    }
}