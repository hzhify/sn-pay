<?php

/**
 * @name Js
 * @description 微信公众号支付
 * @author houzhi
 * @time 2018/10/30 16:50
 */

namespace pay\wx\pay;

use pay\util\Func;

class Js extends WxPayBaseStrategy
{
    protected $extValidFields = ['openid'];

    protected function setTradeType()
    {
        $this->data['trade_type'] = 'JSAPI';
    }

    public function aopClientRequestExecuteCallback($result)
    {
        $data = [
            'appId'     => $this->config['public_app_id'],
            'timeStamp' => time(),
            'nonceStr'  => Func::getNonceStr(),
            'package'   => "prepay_id={$result['prepay_id']}",
            'signType'  => 'MD5',
        ];
        $data['paySign'] = Func::sign($data, $this->config['public_key']);
        return $data;
    }
}