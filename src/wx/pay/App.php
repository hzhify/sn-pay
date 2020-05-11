<?php

/**
 * @name App
 * @description 微信APP支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;
use pay\util\Func;

class App extends WxPayBaseStrategy {

    protected function setTradeType() {
        $this->data['trade_type'] = 'APP';
    }

    public function aopClientRequestExecuteCallback($result) {
        // 如果是新版本就在服务器签名，并将相关配置返回
        if (!empty($this->data['is_new'])) {
            $res = [
                'appid'     => $this->config['app_id'],
                'partnerid' => $this->config['mch_id'],
                'prepayid'  => $result['prepay_id'],
                'package'   => 'Sign=WXPay',
                'noncestr'  => Func::getNonceStr(16),
                'timestamp' => time(),
            ];
            $res['sign'] = Func::sign($res, $this->config['md5_key']);
            return json_encode($res);
        } else // 旧版本就只是返回预支付ID
            return $result['prepay_id'];
    }
}