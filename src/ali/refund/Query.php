<?php

/**
 * @name Query
 * @description 退款查询
 * @author houzhi
 * @time 2019/2/21 20:34
 */

namespace pay\ali\refund;

use pay\ali\AliBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Query extends AliBaseStrategy {

    public function execute() {
        if (Func::validParams($this->data, ['out_trade_no', 'trade_no', 'out_refund_no'])) {
            $this->data['out_request_no'] = $this->data['out_refund_no'];
            $this->data = Func::arrayFilterKey($this->data, ['out_trade_no', 'trade_no', 'out_request_no']);
            //获取交易请求实例
            $request = new \AlipayTradeFastpayRefundQueryRequest();
            $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
            return $this->aopClientRequestExecute($request, 'execute'); //发起支付请求
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
            $result = $result['alipay_trade_fastpay_refund_query_response'];
            if (!empty($result['code']) && $result['code'] == 10000) { //转账成功
                $data = [
                    'origin_fee' => ($result['total_amount'] ?? 0) * 100, // 订单原始交易金额
                    'total_fee'  => ($result['refund_amount'] ?? 0) * 100,
                ];
                !empty($result['gmt_refund_pay']) && $data['pay_time'] = strtotime($result['gmt_refund_pay']);
                !empty($result['refund_reason']) && $data['remark'] = $result['refund_reason'];
                return $data;
            }
            Err::getInstance()->add($result['sub_msg'], '*', $result['sub_code']);
        }
        return false;
    }

}