<?php

/**
 * @name MiniApp
 * @description 百度小程序
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\bd\pay;

use pay\bd\BdBaseStrategy;
use pay\util\Func;

class MiniApp extends BdBaseStrategy
{
    public function execute()
    {
        $fields = 'subject,total_fee,out_trade_no';
        if (Func::validParams($this->data, $fields)) {
            $this->data['total_amount'] = $this->data['total_fee'];
            $result = Func::arrayFilterKey($this->data, 'body,subject,out_trade_no,time_expire,total_amount');
            $addData = [
                'appKey'    => $this->config['app_key'],
                'dealId'    => $this->config['deal_id'],
                'tpOrderId' => $result['out_trade_no'],
            ];
            $addData['sign'] = $this->getSign($addData);
            return array_merge($result, $addData);
        }
        return false;
    }
}