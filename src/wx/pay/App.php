<?php

/**
 * @name App
 * @description 微信APP支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;

class App extends WxPayBaseStrategy
{

    protected function setTradeType()
    {
        $this->data['trade_type'] = 'APP';
    }

    public function aopClientRequestExecuteCallback($result)
    {
        return $result['prepay_id'];
    }

}