<?php

/**
 * @name Trans
 * @description 转账
 * @author houzhi
 * @time 2017/11/25 15:51
 */

namespace pay\ali\trans;

use pay\ali\AliBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Trans extends AliBaseStrategy {
    protected $execFunc = 'execute';

    /**
     * 校验和重构数据
     * @return bool
     */
    public function validAndRefactorData() {
        if (Func::validParams($this->data, ['payee_account', 'total_fee', 'real_name'])) {
            $this->data['out_biz_no'] = $this->data['out_trade_no'];
            $this->data['amount'] = $this->data['total_fee'] / 100;
            $this->data['payee_real_name'] = $this->data['real_name'];
            return true;
        }
        return false;
    }

    public function execute() {
        if ($this->validAndRefactorData()) {
            $this->data = Func::arrayFilterKey($this->data, 'out_biz_no,amount,payee_account,payer_show_name,payee_real_name,remark');
            //获取交易请求实例
            $this->data['payee_type'] = 'ALIPAY_LOGONID';
            $request = new \AlipayFundTransToaccountTransferRequest();
            $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
            Func::log($request, $this->logFile);
            return $this->aopClientRequestExecute($request, $this->execFunc); //发起支付请求
        }
        return false;
    }

    /**
     * 转账结果处理
     * @param $result
     * @return array|bool
     */
    public function aopClientRequestExecuteCallback($result) {
        if (!empty($result)) {
            $result = Func::objectToArray($result);
            $result = $result['alipay_fund_trans_toaccount_transfer_response'];
            if (!empty($result['code']) && $result['code'] == 10000) { //转账成功
                return [
                    'out_trade_no' => $this->data['out_biz_no'],
                    'trade_no'     => $result['order_id'],
                    'pay_time'     => strtotime($result['pay_date']),
                ];
            }
            Err::getInstance()->add($result['sub_msg'], '*', $result['sub_code']);
        }
        return false;
    }

}