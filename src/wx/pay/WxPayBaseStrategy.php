<?php

/**
 * @name WxPayBaseStrategy
 * @description 微信支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx\pay;

use pay\util\Func;
use pay\util\Err;
use pay\wx\WxBaseStrategy;

abstract class WxPayBaseStrategy extends WxBaseStrategy
{
    protected $extValidFields = [];

    public function execute()
    {
        if ($this->validAndRefactorData()) {
            $this->setTradeType();
            $this->data = Func::arrayFilterKey($this->data, 'body,out_trade_no,time_expire,time_start,total_fee,spbill_create_ip,trade_type,scene_info,openid');
            return $this->clientRequestExecute($this->data); //发起支付请求
        }
        return false;
    }

    /**
     * 设置交易类型
     * @return mixed
     */
    abstract protected function setTradeType();

    /**
     * 校验数据
     * @return mixed
     */
    public function validAndRefactorData()
    {
        $fields = ['subject', 'total_fee', 'notify_url', 'out_trade_no'];
        !empty($this->extValidFields) && $fields = array_merge($fields, $this->extValidFields);
        if (Func::validParams($this->data, $fields)) {
            if (!(empty($this->data['timeout']) && empty($this->config['timeout']))) {
                //如果默认超时时间为1天
                $timeout = empty($this->data['timeout']) || !is_numeric($this->data['timeout']) || $this->data['timeout'] > 86400 ? $this->config['timeout'] : $this->data['timeout'];
                $startTime = $this->data['start_time'] ?? time();
                $this->data['time_start'] = date('YmdHis', $startTime);
                $this->data['time_expire'] = date('YmdHis', $startTime + $timeout);
            }
        }
        return false;
    }

}
