<?php

/**
 * @name Trans
 * @description 微信转账
 * @author houzhi
 * @time 2017/11/27 23:21
 */

namespace pay\wx\trans;

use pay\wx\WxBaseStrategy;
use pay\util\Func;

class Trans extends WxBaseStrategy {

    protected $gatewayUrl = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';

    public function execute() {
        if ($this->validAndRefactorData()) {
            $this->data = Func::arrayFilterKey($this->data, 'partner_trade_no,openid,check_name,re_user_name,amount,desc');
            $this->data['mch_appid'] = $this->config['app_id'];
            $this->data['mchid'] = $this->config['mch_id'];
            $this->data['spbill_create_ip'] = Func::getClientIp();
            $certs = [
                'cert' => $this->config['ssl_cert_path'],
                'key'  => $this->config['ssl_key_path'],
            ];
            return $this->clientRequestExecute($this->data, $certs);
        }
        return false;
    }

    private function validAndRefactorData() {
        $fields = 'open_id,out_trade_no,total_fee,subject';
        if (!Func::validParams($this->data, $fields)) {
            return false;
        }
        if (isset($this->data['check_name']) && !in_array($this->data['check_name'], [0, 1])) {
            $this->err->add('是否校验真实姓名选项不正确');
            return false;
        }
        if (isset($this->data['check_name']) && $this->data['check_name'] == 1) {
            if (empty($this->data['real_name'])) {
                $this->err->add('真实姓名不能为空');
                return false;
            }
            $this->data['check_name'] = 'FORCE_CHECK';
            $this->data['re_user_name'] = $this->data['real_name'];
        } else {
            $this->data['check_name'] = 'NO_CHECK';
        }

        if ($this->data['total_fee'] < 100) {
            $this->err->add('转账金额至少1元');
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

}