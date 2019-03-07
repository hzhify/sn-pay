<?php

/**
 * @name Refund
 * @description 退款
 * @author houzhi
 * @time 2019/2/21 20:34
 */

namespace pay\ali\refund;

use pay\ali\AliBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Refund extends AliBaseStrategy {

    /**
     * 校验和重构数据
     * @return bool
     */
    public function validAndRefactorData() {
        if (Func::validParams($this->data, ['out_trade_no', 'total_fee', 'trade_no', 'remark', 'out_refund_no'])) {
            if ($this->data['total_fee'] > 0) {
                $this->data['refund_reason'] = $this->data['remark'];
                $this->data['out_request_no'] = $this->data['out_refund_no'];
                $this->data['refund_amount'] = $this->data['total_fee'] / 100;
                return true;
            }
        }
        return false;
    }

    public function execute() {
        if ($this->validAndRefactorData()) {
            $this->data = Func::arrayFilterKey($this->data, 'out_trade_no,refund_amount,trade_no,refund_reason,out_request_no');
            //获取交易请求实例
            $request = new \AlipayTradeRefundRequest();
            $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
            return $this->aopClientRequestExecute($request, 'execute'); //发起退款请求
        }
        return false;
    }

    /**
     * 退款结果处理
     * @param $result
     * @return array|bool
     */
    public function aopClientRequestExecuteCallback($result) {
        if (!empty($result)) {
            $result = Func::objectToArray($result);
            $result = $result['alipay_trade_refund_response'];
            if (!empty($result['code']) && $result['code'] == 10000) { // 退款成功
                return [
                    'pay_time' => strtotime($result['gmt_refund_pay']),
                ];
            }
            Err::getInstance()->add($result['sub_msg'], '*', $result['sub_code']);
        }
        return false;
    }

}