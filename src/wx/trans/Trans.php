<?php

/**
 * @name Trans
 * @description 微信转账
 * @author houzhi
 * @time 2017/11/27 23:21
 */

namespace vApp\lib\src\wx\trans;

use pay\wx\WxBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class Trans extends WxBaseStrategy
{
    public function execute()
    {
        if ($this->validAndRefactorData()) {
            $this->data = Func::arrayFilterKey($this->data, 'partner_trade_no,openid,check_name,re_user_name,amount,desc');
            return $this->clientRequestExecute($this->data);
        }
        return false;
    }

    private function validAndRefactorData()
    {
        $fields = 'open_id,out_trade_no,total_fee,subject';
        if (!Func::validParams($this->data, $fields)) {
            return false;
        }
        $err = Err::getInstance();
        if (isset($this->data['check_name']) && !in_array($this->data['check_name'], [0, 1])) {
            $err->add('是否校验真实姓名选项不正确');
            return false;
        }
        if (isset($this->data['check_name']) && $this->data['check_name'] == 1) {
            if (empty($this->data['real_name'])) {
                $err->add('真实姓名不能为空');
                return false;
            }
            $this->data['check_name'] = 'FORCE_CHECK';
            $this->data['re_user_name'] = $this->data['real_name'];
        } else {
            $this->data['check_name'] = 'NO_CHECK';
        }

        if ($this->data['total_fee'] < 100) {
            $err->add('转账金额至少1元');
            return false;
        }
        $renameFields = [
            'open_id'      => 'openid',
            'out_trade_no' => 'partner_trade_no',
            'total_fee'    => 'amount',
            'subject'      => 'desc',
        ];
        Func::arrayReKey($this->data, $renameFields);
        return true;
    }

    /**
     * 执行请求
     * @param $data
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $data['mch_appid'] = $this->config['app_id'];
        $data['mchid'] = $this->config['mch_id'];
        $data['nonce_str'] = Func::getNonceStr();
        $data['spbill_create_ip'] = Func::getClientIp();
        $data['sign'] = Func::sign($data, $this->config['md5_key']);
        $xml = Func::toXml($data);
        $certs = [
            'cert' => $this->config['ssl_cert_path'],
            'key'  => $this->config['ssl_key_path'],
        ];
        $response = $this->postXmlCurl($xml, $url, $certs, $timeOut);
        if ($response) {
            $result = Func::xmlToArray($response);
            if (!empty($result['return_code']) && $result['return_code'] === 'SUCCESS' && !empty($result['result_code']) && $result['result_code'] === 'SUCCESS') {
                return [
                    'out_trade_no' => $result['partner_trade_no'],
                    'pay_time'     => strtotime($result['payment_time']),
                    'trade_no'     => $result['payment_no'],
                ];
            } else {
                $errCode = empty($result['err_code']) ? $result['return_code'] : $result['err_code'];
                $errMsg = empty($result['err_code_des']) ? $result['return_msg'] : $result['err_code_des'];
                Err::getInstance()->add(['msg' => $errMsg, 'code' => $errCode]);
            }
        }
        return false;
    }
}