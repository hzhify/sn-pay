<?php

/**
 * @name Notify
 * @description 支付宝异步回调通知
 * @author houzhi
 * @time 2017/11/25 17:15
 */

namespace pay\ali\notify;

use pay\ali\AliBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Notify extends AliBaseStrategy
{
    public function execute()
    {
        $err = Err::getInstance();
        if (!(isset($this->data['trade_status']) && ($this->data['trade_status'] === 'TRADE_SUCCESS' || $this->data['trade_status'] === 'TRADE_FINISHED'))) {
            $err->add('支付状态不正确');
            Func::log('ali-notify-execute-start-status-error', $this->logFile);
            return false;
        }
        if ($this->checkSign($this->data)) {
            $fields = 'notify_id,trade_no,out_trade_no,out_biz_no,buyer_id';
            $data = Func::arrayFilterKey($this->data, $fields);
            $data = array_merge($data, [
                'pay_time'      => strtotime($this->data['gmt_payment']),
                'payer_account' => $this->data['buyer_logon_id'],
                'payer_id'      => $this->data['buyer_id'],
                'total_fee'     => $this->data['total_amount'] * 100,
            ]);
            Func::log('ali-notify-execute-success-res:' . json_encode($data), $this->logFile);
            return $data;
        }
        $err->add('签名验证错误');
        Func::log('ali-notify-execute-sign-error', $this->logFile);
        return false;
    }
}