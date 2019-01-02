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

class Notify extends AliBaseStrategy
{
    public function execute()
    {
        if (!(isset($this->data['trade_status']) && ($this->data['trade_status'] === 'TRADE_SUCCESS' || $this->data['trade_status'] === 'TRADE_FINISHED'))) {
            return false;
        }
        if ($this->checkSign($this->data)) {
            $fields = 'notify_id,trade_no,out_trade_no,out_biz_no,buyer_id';
            $data = Func::arrayFilterKey($this->data, $fields);
            $data = array_merge($data, [
                'pay_time'      => strtotime($this->data['gmt_payment']),
                'buyer_account' => $this->data['buyer_logon_id'],
            ]);
            return $data;
        }
        return false;
    }
}