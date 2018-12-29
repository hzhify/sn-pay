<?php

/**
 * @name WxTrans
 * @description 微信转账
 * @author houzhi
 * @time 2017/11/27 23:21
 */

namespace vApp\lib\src\wx\trans;

use v;
use vApp;

class WxTrans extends vApp\lib\src\wx\WxBaseStrategy {

    protected $config = [];

    protected $data = [];


    public function __construct(array $data, array $config) {
        $this->config = $config;
        $this->data = $data;
    }

    public function handle() {
        if ($this->validData() && $this->saveData()) {
            $id = $this->data['_id']; //获取数据记录ID
            $this->data = array_filter_key($this->data, 'partner_trade_no,openid,check_name,re_user_name,amount,desc');
            $result = $this->clientRequestExecute($this->data);
            if ($result) {
                $payInfo = [
                    'out_trade_no' => $result['partner_trade_no'],
                    'pay_time' => strtotime($result['payment_time']),
                    'trade_no' => $result['payment_no'],
                ];
                v\App::model('Payment')->paySuccess($payInfo, $id, 'wechat');
                $fields = ['out_trade_no', 'subject', 'total_fee', 'trade_type', 'pay_way', 'mch_id', 'pay_time', 'trade_no', 'check_name', 'real_name', 'remark', 'open_id'];
                $return = v\App::model('Payment')->getByID($id, ['field' => $fields]);
                unset($return['_id']);
                return $return;
            }
        }
        return false;
    }

    private function validData() {
        if (empty($this->data['open_id'])) {
            v\Err::add('OPENID不能为空');
            return false;
        }
        $this->data['openid'] = $this->data['open_id'];
        if (empty($this->data['out_trade_no'])) {
            v\Err::add('交易编号不能为空');
            return false;
        }
        $this->data['partner_trade_no'] = $this->data['out_trade_no'];
        if (isset($this->data['check_name']) && !in_array($this->data['check_name'], [0, 1])) {
            v\Err::add('是否校验真实姓名选项不正确');
            return false;
        }
        if (isset($this->data['check_name']) && $this->data['check_name'] == 1) {
            if (empty($this->data['real_name'])) {
                v\Err::add('真实姓名不能为空');
                return false;
            }
            $this->data['check_name'] = 'FORCE_CHECK';
            $this->data['re_user_name'] = $this->data['real_name'];
        } else {
            $this->data['check_name'] = 'NO_CHECK';
        }
        if (empty($this->data['total_fee']) || $this->data['total_fee'] < 100) {
            v\Err::add('转账金额至少1元');
            return false;
        }
        $this->data['amount'] = $this->data['total_fee'];
        if (empty($this->data['subject'])) {
            v\Err::add('交易描述不能为空');
            return false;
        }
        $this->data['desc'] = $this->data['subject'];
        return true;
    }

    private function saveData() {
        $model = v\App::model('Payment');
        $item = $model->getByOutTradeNo($this->data['out_trade_no']);
        //如果转账信息不存在，则添加支付休息
        if (empty($item)) {
            $rs = $model->setData($this->data)->isMust() && $model->addOne();
            if ($rs) {
                $this->data['_id'] = $model->lastID();
            }
        } else { //更新数据
            if ($item['status'] === 1) {
                v\Err::add('已转账成功');
                return false;
            }
            $this->data['req_time'] = time();
            $rs = $model->setData($this->data)->subData($item)->isValid() && $model->upByID($item['_id']);
            $this->data['_id'] = $item['_id'];
        }
        return $rs;
    }


    /**
     * 执行请求
     * @param $data
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $timeOut = 6) {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $data['mch_appid'] = $this->config['app_id'];
        $data['mchid'] = $this->config['mch_id'];
        $data['nonce_str'] = vApp\lib\Extension::getNonceStr();
        $data['spbill_create_ip'] = vApp\lib\Extension::getClientIp();
        $data['sign'] = vApp\lib\Extension::sign($data, $this->config['md5_key']);
        $xml = vApp\lib\Extension::toXml($data);
        $certs = [
            'cert' => $this->config['ssl_cert_path'],
            'key' => $this->config['ssl_key_path'],
        ];
        $response = $this->postXmlCurl($xml, $url, $certs, $timeOut);
        if ($response) {
            v\App::log($response, 'test.log');
            $result = vApp\lib\Extension::xmlToArray($response);
            v\App::log($result, 'test.log');
            $flag = !empty($result['return_code']) && $result['return_code'] === 'SUCCESS' && !empty($result['result_code']) && $result['result_code'] === 'SUCCESS';
            if ($flag) {
                return $result;
            } else {
                $errCode = empty($result['err_code']) ? $result['return_code'] : $result['err_code'];
                $errMsg = empty($result['err_code_des']) ? $result['return_msg'] : $result['err_code_des'];
                v\Err::add(['msg' => $errMsg, 'code' => $errCode]);
                return false;
            }
        }
        return false;
    }
}