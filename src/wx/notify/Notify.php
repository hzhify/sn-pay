<?php

/**
 * @name Notify
 * @description 微信异步回调通知
 * @author houzhi
 * @time 2017/11/25 17:15
 */

namespace pay\wx\notify;

use pay\util\Func;
use pay\wx\WxBaseStrategy;

class Notify extends WxBaseStrategy
{
    public function execute()
    {
        $params = @file_get_contents('php://input');
        if (empty($params)) {
            $this->err->add('数据为空');
            return false;
        } else {
            $this->data = Func::xmlToArray($params);
        }
        $flag = !empty($this->data['return_code']) && $this->data['return_code'] == 'SUCCESS' && !empty($this->data['result_code']) && $this->data['result_code'] == 'SUCCESS';
        if (!$flag) {
            $this->err->add('状态不正确');
            return false;
        }
        // 验签
        unset($this->data['trade_way']);
        if ($this->data['trade_type'] == 'JSAPI') {
            $signKey = $this->config['public_key'];
            $appId = $this->config['public_app_id'];
        } else {
            $signKey = $this->config['md5_key'];
            $appId = $this->config['app_id'];
        }
        if (empty($this->data['sign']) || !(Func::checkSign($this->data, $signKey))) {
            $this->err->add('签名不正确');
            return false;
        }

        if ($appId !== $this->data['app_id']) { //校验appid是否为该商户本身
            $this->err->add('数据不正确');
            return false;
        }
        return [
            'trade_no'     => $this->data['transaction_id'],
            'pay_time'     => strtotime(strval($this->data['time_end'])),
            'open_id'      => $this->data['openid'],
            'total_fee'    => $this->data['total_fee'],
            'out_trade_no' => $this->data['out_trade_no'],
        ];
    }
}