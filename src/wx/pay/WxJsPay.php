<?php

/**
 * @name WxJsPay
 * @description 微信公众号支付
 * @author houzhi
 * @time 2018/10/30 16:50
 */

namespace vApp\lib\src\wx\pay;

use v, vApp;

class WxJsPay extends WxPayBaseStrategy {

    protected function setTradeType() {
        $this->data['trade_type'] = 'JSAPI';
    }

    public function validData() {
        if (empty($this->data['openid'])) {
            v\Err::add('OPENID不能为空', 'openid');
            return false;
        }
        return parent::validData();
    }

    public function handle() {
        if ($this->validData() && $this->saveData()) {
            $this->setTradeType();
            $this->data = array_filter_key($this->data, 'body,out_trade_no,time_expire,time_start,total_fee,spbill_create_ip,trade_type,scene_info,openid');
            //发起支付请求
            if ($result = $this->clientRequestExecute($this->data)) {
                $data = [
                    'appId' => $this->config['public_app_id'],
                    'timeStamp' => time(),
                    'nonceStr' => vApp\lib\Extension::getNonceStr(),
                    'package' => "prepay_id={$result['prepay_id']}",
                    'signType' => 'MD5',
                ];
                $data['paySign'] = vApp\lib\Extension::sign($data, $this->config['public_key']);
                return $data;
            }
        }
        return false;
    }
}