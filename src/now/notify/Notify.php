<?php

/**
 * @name Notify
 * @description 异步回调通知
 * @author houzhi
 * @time 2019/09/28 10:57
 */

namespace pay\now\notify;

use pay\now\NowBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Notify extends NowBaseStrategy {

    public function handle() {
        $err = Err::getInstance();
        $logPre = '';
        try {
            $paramStr = @file_get_contents('php://input');
            if (empty($paramStr)) {
                $err->add('数据为空');
                return false;
            }
            Func::log("NowPayNotify|$paramStr", $this->logFile);
            $params = [];
            parse_str($paramStr, $params);
            $logPre = "{$params['mhtOrderNo']}|now-pay-notify|";
            // 必须字段
            $fields = 'funcode,appId,mhtOrderNo,mhtOrderName,mhtOrderType,mhtOrderAmt,mhtOrderStartTime,payTime,nowPayOrderNo,deviceType,payChannelType,channelOrderNo,payConsumerId,signType,signature';
            if (!Func::validParams($params, $fields)) {
                Func::log("{$logPre}params-err|" . __LINE__, $this->logFile);
                $err->add('参数不正确');
                return false;
            }
            $deviceTypeConf = $this->config['device_type'];
            $payWay = array_flip($deviceTypeConf)[$params['deviceType']]; // 获取支付方式

            // 判断支付状态
            $appFalse = $payWay === 'app' && (!isset($params['tradeStatus']) || $params['tradeStatus'] != 'A001');
            $wapAndQrFalse = in_array($payWay, ['qr', 'wap']) && (!isset($params['transStatus']) && $params['transStatus'] != 'A001');
            if ($appFalse || $wapAndQrFalse) {
                Func::log("{$logPre}pay-status-err|" . __LINE__, $this->logFile);
                $err->add('参数不正确');
                return false;
            }
            
            // 校验签名
            if ($this->getSignStr($params, $this->config["{$payWay}_app_key"]) != $params['signature']) {
                Func::log("{$logPre}sign-err|" . __LINE__, $this->logFile);
                $err->add('签名不正确');
                return false;
            }

            $fields = [
                'mhtOrderNo'     => 'out_trade_no',
                'mhtOrderAmt'    => 'total_fee',
                'payTime'        => 'pay_time',
                'payConsumerId'  => 'payer_account',
                'channelOrderNo' => 'trade_no',
                'nowPayOrderNo'  => 'ch_trade_no',
            ];

            $result = [];
            foreach ($fields as $k => $v) {
                if (!isset($params[$k]))
                    continue;
                $result[$fields[$k]] = $params[$k];
            }
            $result['pay_time'] = strtotime($result['pay_time']);
            Func::log($logPre . 'notify-suc|' . json_encode($result), $this->logFile);
            return $result;
        } catch (\Exception $e) {
            Func::log($logPre . 'error|' . $e->getMessage() . '|' . $e->getLine() . '|' . $e->getTraceAsString(), $this->logFile);
        }
        return false;
    }
}