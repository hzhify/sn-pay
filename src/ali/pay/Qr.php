<?php

/**
 * @name Qr
 * @description 支付宝扫码支付
 * @author houzhi
 * @time 2018/2/1 17:00
 */

namespace pay\ali\pay;

use pay\util\Err;

class Qr extends AliPayBaseStrategy
{
    protected $execFunc = 'execute';

    /**
     * 获取交易请求实例
     * @return \AlipayTradePrecreateRequest
     */
    public function getTradeRequestInstance()
    {
        $request = new \AlipayTradePrecreateRequest();
        $request->setBizContent(json_encode($this->data));
        $request->setNotifyUrl($this->config['notify_url']);
        return $request;
    }

    public function aopClientRequestExecuteCallback($result)
    {
        if (!empty($result) && !empty($result->alipay_trade_precreate_response)) {
            $result = json_decode(json_encode($result->alipay_trade_precreate_response), true);
            if (!empty($result) && $result['code'] === '10000') {
                return ['code_url' => $result['qr_code']];
            } else {
                Err::getInstance()->add($result['msg'], $result['code']);
            }
        }
        return false;
    }
}