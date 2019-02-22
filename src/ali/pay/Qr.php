<?php

/**
 * @name Qr
 * @description 支付宝扫码支付
 * @author houzhi
 * @time 2018/2/1 17:00
 */

namespace pay\ali\pay;

use pay\util\Err;
use pay\util\Func;

class Qr extends AliPayBaseStrategy {
    protected $execFunc = 'execute';

    protected $qrCodeDir = '';

    /**
     * 获取交易请求实例
     * @return \AlipayTradePrecreateRequest
     */
    public function getTradeRequestInstance() {
        $request = new \AlipayTradePrecreateRequest();
        $this->qrCodeDir = $this->data['qr_code_dir'] ?? '';
        unset($this->data['qr_code_dir']);
        $request->setBizContent(json_encode($this->data));
        $request->setNotifyUrl($this->data['notify_url']);
        return $request;
    }

    public function aopClientRequestExecuteCallback($result) {
        if (!empty($result) && !empty($result->alipay_trade_precreate_response)) {
            $result = json_decode(json_encode($result->alipay_trade_precreate_response), true);
            if (!empty($result) && $result['code'] === '10000') {
                return [
                    'code_img_url' => Func::getQrCode($result['qr_code'], 'ali_' . $this->data['out_trade_no'], $this->qrCodeDir),
                    'code_url'     => $result['qr_code']
                ];
            } else {
                $msg = $result['sub_msg'] ?? $result['msg'];
                Err::getInstance()->add($msg, $result['code']);
            }
        }
        return false;
    }
}