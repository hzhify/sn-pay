<?php

/**
 * @name WxAppPay
 * @description 微信APP支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace vApp\lib\src\wx\pay;

use v, vApp;

class WxAppPay extends WxPayBaseStrategy {

    protected function setTradeType() {
        $this->data['trade_type'] = 'APP';
    }


    public function handle() {
        $result = parent::handle();
        v\App::log($result, 'test.log');
        if ($result) {
            return $result['prepay_id'];
        }
        return false;
    }
}