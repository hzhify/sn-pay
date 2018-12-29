<?php

/**
 * @name App
 * @description 支付宝APP支付
 * @author houzhi
 * @time 2017/11/22 22:23
 */

namespace pay\ali\pay;

class App extends AliPayBaseStrategy
{
    protected $execFunc = 'sdkExecute';

    /**
     * 获取交易请求实例
     * @return \AlipayTradeAppPayRequest
     */
    public function getTradeRequestInstance()
    {
        $this->data['product_code'] = 'QUICK_MSECURITY_PAY';
        $request = new \AlipayTradeAppPayRequest();
        $request->setNotifyUrl($this->data['notify_url']);
        $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
        return $request;
    }
}