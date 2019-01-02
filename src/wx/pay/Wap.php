<?php

/**
 * @name Wap
 * @description 微信H5支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;

use pay\util\Func;

class Wap extends WxPayBaseStrategy
{
    protected $extValidFields = ['bill_create_ip', 'ter_os'];

    protected function setTradeType()
    {
        $this->data['trade_type'] = 'MWEB';
    }


    public function handle()
    {
        $result = parent::handle();
        if ($result) {
            $token = base64_encode($this->data['out_trade_no']);
            $payUrl = $this->config['host'] . '/pay/wxWap.html?token=' . $token;
            $payUrl .= '&s=' . md5($token . $this->config['secretKey']); //在支付url中添加一个参数s，作用是：校验后面的支付请求是否合法
            $timeout = empty($this->data['timeout']) || $this->data['timeout'] > 86400 || !is_numeric($this->data['timeout']) ? $this->config['timeout'] : $this->data['timeout'];
            v\Redis::set('wx_wap_t_' . $this->data['out_trade_no'], $result['mweb_url'], $timeout);
            return $payUrl;
        }
        return false;
    }
}