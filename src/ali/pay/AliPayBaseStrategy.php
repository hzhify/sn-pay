<?php

/**
 * @name AliPayBaseStrategy
 * @description 支付宝支付
 * @author houzhi
 * @time 2017/11/23 17:42
 */

namespace pay\ali\pay;

use pay\ali\AliBaseStrategy;
use pay\util\Func;

abstract class AliPayBaseStrategy extends AliBaseStrategy
{
    protected $execFunc = '';

    public function execute()
    {
        if ($this->validAndRefactorData()) {
            $fields = 'body,subject,out_trade_no,timeout_express,total_amount,passback_params,notify_url,return_url,qr_code_dir,disable_pay_channels';
            $this->data = Func::arrayFilterKey($this->data, $fields);
            $request = $this->getTradeRequestInstance(); //获取交易请求实例
            return $this->aopClientRequestExecute($request, $this->execFunc); //发起支付请求
        }
        return false;
    }

    /**
     * 校验和重构数据
     * @return bool
     */
    public function validAndRefactorData()
    {
        if (Func::validParams($this->data, ['subject', 'total_fee', 'notify_url', 'out_trade_no'])) {
            // 接口调用传的单位是分，而支付宝要求，的是元
            $this->data['total_amount'] = $this->data['total_fee'] / 100;
            if (!(empty($this->data['timeout']) && empty($this->config['timeout']))) {
                //如果默认超时时间为1天
                $timeout = empty($this->data['timeout']) || !is_numeric($this->data['timeout']) || $this->data['timeout'] > 86400 ? $this->config['timeout'] : $this->data['timeout'];
                $this->data['timeout_express'] = intval($timeout / 60) . 'm';
            }
            if (!empty($this->data['remark']))
                $this->data['body'] = $this->data['remark'];
            if (!empty($this->data['extend_info']))
                $this->data['passback_params'] = $this->data['extend_info'];
            return true;
        }
        return false;
    }

    /**
     * 获取交易请求实例
     * @return mixed
     */
    abstract public function getTradeRequestInstance();


}