<?php
/**
 * @name NowPayBaseStrategy
 * @description
 * @author houzhi
 * @time 2019/09/27 14:18
 */

namespace pay\now\pay;

use pay\now\NowBaseStrategy;
use pay\util\Func;

abstract class NowPayBaseStrategy extends NowBaseStrategy {

    protected $payType = '';

    protected $reKeyArr = [
        'out_trade_no' => 'mhtOrderNo',
        'subject'      => 'mhtOrderName',
        'notify_url'   => 'notifyUrl',
        'return_url'   => 'frontNotifyUrl',
        'total_fee'    => 'mhtOrderAmt',
        'itime'        => 'mhtOrderStartTime',
    ];

    /**
     * 聚合动态码：https://nc.ipaynow.cn/s/R75dW3oyom4HKRo
     * 主扫：https://nc.ipaynow.cn/s/djpH99oFrjK3Eqw
     * 手机APP：https://nc.ipaynow.cn/s/xDGNR56JJK78m9e
     * 手机网页：https://nc.ipaynow.cn/s/7w7DjGRwwxrKfwM
     * @return bool|string
     */
    public function handle() {
        if ($data = $this->validAndRefactorData()) {
            $fields = 'funcode,version,appId,mhtOrderNo,mhtSubMchId,mhtOrderName,mhtOrderType,mhtCurrencyType,mhtOrderAmt,mhtOrderDetail,mhtOrderTimeOut,mhtOrderStartTime,notifyUrl,mhtCharset,deviceType,payChannelType,mhtReserved,mhtSubAppId,mhtLimitPay,mhtGoodsTag,mhtSignType,frontNotifyUrl,outputType';
            $data = Func::arrayFilterKey($data, $fields);
            $reqParamStr = $this->getParamStr($data); // 拼接请求参数得到字符串
            $sign = md5("$reqParamStr" . md5($this->config["{$this->payType}_app_key"])); // 生成签名字符串
            $reqParamStr = "{$reqParamStr}mhtSignature=$sign"; // 生成最终的请求参数字符串
            Func::log(__CLASS__ . '|' . __LINE__ . '|pay-req-data:' . $reqParamStr, $this->logFile);
            return $reqParamStr;
        }
        return false;
    }

    /**
     * 校验和重构数据
     * @return bool
     */
    public function validAndRefactorData() {
        if (!Func::validParams($this->data, ['subject', 'total_fee', 'notify_url', 'out_trade_no']))
            return false;

        $this->data['mhtOrderDetail'] = $this->data['remark'] ?? $this->data['subject'];
        $this->data['mhtOrderTimeOut'] = $this->data['timeout'] ?? $this->config['timeout'];
        // $this->data['mhtReserved'] = $this->data['extend_info'] ?? '';
        $this->data = array_merge($this->data, $this->config['req_fixed_params']); // 添加公有不变的参数
        $this->data['appId'] = $this->config["{$this->payType}_app_id"];
        Func::arrayReKey($this->data, $this->reKeyArr); // 字段重命名
        $this->data['mhtOrderStartTime'] = date('YmdHis', $this->data['mhtOrderStartTime']);
        $this->data['deviceType'] = $this->config['device_type'][$this->payType];
        $this->data['mhtOrderType'] = $this->config['order_type'][$this->payType];
        return $this->data;
    }


}