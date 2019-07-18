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

class Notify extends AliBaseStrategy {
    public function execute() {
        try {
            $err = Err::getInstance();
            Func::log('ali-notify-execute|start-data-' . json_encode($this->data), $this->logFile);
            $logPre = "{$this->data['out_trade_no']}|ali-notify-execute|";
            if (!(isset($this->data['trade_status']) && ($this->data['trade_status'] === 'TRADE_SUCCESS' || $this->data['trade_status'] === 'TRADE_FINISHED'))) {
                $err->add('支付状态不正确');
                Func::log($logPre . 'status-error', $this->logFile);
                return false;
            }
            if ($this->config['app_id'] != $this->data['app_id']) {
                $err->add('配置不正确');
                Func::log($logPre . 'config-error', $this->logFile);
                return false;
            }
            if ($this->checkSign($this->data)) {
                $fields = 'notify_id,trade_no,out_trade_no,out_biz_no,buyer_id';
                $data = Func::arrayFilterKey($this->data, $fields);
                $data = array_merge($data, [
                    'pay_time'      => strtotime($this->data['gmt_payment']),
                    'payer_account' => $this->data['buyer_logon_id'] ?? '',
                    'payer_id'      => $this->data['buyer_id'],
                    'total_fee'     => intval(bcmul($this->data['total_amount'], 100)),
                ]);
                Func::log($logPre . 'notify-suc|' . json_encode($data), $this->logFile);
                return $data;
            }
            $err->add('签名验证错误');
            Func::log($logPre . 'sign-error', $this->logFile);
        } catch (\Exception $e) {
            v\App::log($logPre . 'error|' . $e->getMessage() . '|' . $e->getLine() . '|' . $e->getTraceAsString(), $this->logFile);
        }
        return false;
    }
}