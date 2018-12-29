<?php

/**
 * @name BaiduMiniAppPay
 * @description 微信APP支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace vApp\lib\src\baidu\pay;

use v, vApp;

class BaiduMiniAppPay extends BaiduPayBaseStrategy {

    public function handle() {
        $data = parent::handle();
        $addData = [
            'appKey' => $this->config['app_key'],
            'dealId' => $this->config['deal_id'],
            'tpOrderId' => $data['out_trade_no'],
        ];
        $addData['sign'] = $this->getSign($addData);
        return array_merge($data, $addData);
    }
}