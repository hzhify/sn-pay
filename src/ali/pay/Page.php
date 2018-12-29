<?php

/**
 * @name Page
 * @description 支付宝电脑网页支付
 * @author houzhi
 * @time 2017/11/25 14:00
 */

namespace pay\ali\pay;

class Page extends AliPayBaseStrategy
{
    protected $execFunc = 'pageExecute';

    /**
     * 获取交易请求实例
     * @return \AlipayTradeAppPayRequest
     */
    public function getTradeRequestInstance()
    {
        $this->data['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        $request = new \AlipayTradePagePayRequest();
        $request->setNotifyUrl($this->data['notify_url']);
        $request->setReturnUrl($this->data['return_url']);
        $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
        return $request;
    }
}