<?php

/**
 * @name Wap
 * @description 支付网页（H5）支付
 * @author houzhi
 * @time 2017/11/22 22:23
 */

namespace pay\ali\pay;

class Wap extends AliPayBaseStrategy
{
    protected $execFunc = 'pageExecute';

    /**
     * 获取交易请求实例
     * @return \AlipayTradeWapPayRequest
     */
    public function getTradeRequestInstance()
    {
        $this->data['product_code'] = 'QUICK_WAP_PAY';
        $request = new \AlipayTradeWapPayRequest();
        $request->setNotifyUrl($this->data['notify_url']);
        $request->setReturnUrl($this->data['return_url']);
        $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
        return $request;
    }
}