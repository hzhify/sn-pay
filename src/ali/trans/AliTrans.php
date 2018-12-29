<?php

/**
 * @name AliTrans
 * @description 转账
 * @author houzhi
 * @time 2017/11/25 15:51
 */

namespace vApp\lib\src\alipay\trans;

use v;
use vApp;

class AliTrans extends vApp\lib\src\alipay\AliBaseStrategy {

    protected $config = [];

    protected $data = [];


    public function __construct(array $data, array $config) {
        $this->config = $config;
        $this->data = $data;
    }

    public function handle() {
        $model = v\App::model('Payment');
        $fields = ['out_trade_no', 'subject', 'total_fee', 'trade_type', 'pay_way', 'mch_id', 'pay_time', 'trade_no', 'check_name', 'real_name', 'remark', 'payee_account', 'status'];
        $item = $model->getByOutTradeNo($this->data['out_trade_no'], $fields);
        if (!empty($item) && $item['status'] == 1) { //如果是转账成功，则直接返回数据
            unset($item['status'], $item['_id']);
            return $item;
        }
        if ($this->validData() && $this->saveData()) {
            $id = $this->data['_id']; //获取数据记录ID
            $this->data['payee_real_name'] = $this->data['real_name'];
            $this->data = array_filter_key($this->data, 'out_biz_no,amount,payee_account,payer_show_name,payee_real_name,remark');
            $this->data['payee_type'] = 'ALIPAY_LOGONID';
            $request = new \AlipayFundTransToaccountTransferRequest();
            $request->setBizContent(json_encode($this->data, JSON_UNESCAPED_UNICODE));
            $result = $this->aopClientRequestExecute($request);
            if (!empty($result)) {
                $result = $this->objectToArray($result);
                $result = $result['alipay_fund_trans_toaccount_transfer_response'];
                if (!empty($result['code']) && $result['code'] == 10000) { //转账成功
                    $syncData = [
                        'out_trade_no' => $this->data['out_biz_no'],
                        'trade_no' => $result['order_id'],
                        'pay_time' => strtotime($result['pay_date']),
                    ];
                    v\App::model('Payment')->paySuccess($syncData, $id, 'alipay'); //更新数据库状态
                    $return = v\App::model('Payment')->getByID($id, ['field' => $fields]);
                    unset($return['_id']);
                    return $return;
                }
                v\Err::add(['msg' => $result['sub_msg'], 'code' => $result['sub_code']]);
                return false;
            } else {
                v\Err::add('转账失败');
                return false;
            }
        }
    }

    /**
     * 保存数据
     * @return mixed
     */
    public function saveData() {
        $model = v\App::model('Payment');
        $item = $model->getByOutTradeNo($this->data['out_trade_no']);
        //如果转账信息不存在，则添加支付休息
        if (empty($item)) {
            $rs = $model->setData($this->data)->isMust() && $model->addOne();
            if ($rs) {
                $this->data['_id'] = $model->lastID();
            }
        } else { //更新数据
            $this->data['req_time'] = time();
            $rs = $model->setData($this->data)->subData($item)->isValid() && $model->upByID($item['_id']);
            $this->data['_id'] = $item['_id'];
        }
        return $rs;
    }


    /**
     * 发起转账请求
     * @param object $request 请求实例
     * @return \AopClient
     */
    public function aopClientRequestExecute($request) {
        $aop = new \AopClient();
        $aop->gatewayUrl = $this->config['gateway_url'];
        $aop->appId = $this->config['app_id'];
        $aop->rsaPrivateKeyFilePath = $this->config['rsa_private_key'];
        $aop->alipayPublicKey = $this->config['alipay_public_key'];
        $aop->apiVersion = "1.0";
        $aop->postCharset = $this->config['charset'];
        $aop->format = $this->config['return_data_format'];
        $aop->signType = $this->config['sign_type'];
        return $aop->execute($request);
    }


    public function validData() {
        $this->data['out_biz_no'] = $this->data['out_trade_no'];

        if (empty($this->data['total_fee'])) {
            v\Err::add('转账金额不能为空', 'total_fee');
            return false;
        }
        $this->data['amount'] = $this->data['total_fee'] / 100;

        if (empty($this->data['payee_account'])) {
            v\Err::add('收款者账号不能为空', 'payee_account');
            return false;
        }
        return true;
    }

}