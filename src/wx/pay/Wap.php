<?php

/**
 * @name Wap
 * @description 微信H5支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;

use pay\util\Func;

class Wap extends WxPayBaseStrategy {
    protected $extValidFields = ['bill_create_ip', 'ter_os'];

    protected function setTradeType() {
        $this->data['trade_type'] = 'MWEB';
    }

    public function aopClientRequestExecuteCallback($result) {
        return $result['mweb_url'];
    }
}