<?php

/**
 * @name RedPack
 * @description 微信发红包
 * @author houzhi
 * @time 2018/11/19 12:14
 */

namespace pay\wx\trans;

use pay\wx\WxBaseStrategy;
use pay\util\Func;
use pay\util\Err;

class RedPack extends WxBaseStrategy {

    protected $gatewayUrl = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';

    /**
     * 是否是公众号进行支付
     * @var bool
     */
    protected $isPubPay = true;

    /**
     * 校验数据
     * @return mixed
     */
    public function validAndRefactorData() {
        $fields = 'open_id,out_trade_no,wx_red_pack_wishing,wx_red_pack_act_name,wx_red_pack_remark,wx_red_pack_scene_id';
        if (Func::validParams($this->data, $fields)) {
            $renameFields = [
                'wx_red_pack_wishing'  => 'wishing',
                'wx_red_pack_act_name' => 'act_name',
                'wx_red_pack_remark'   => 'remark',
                'wx_red_pack_scene_id' => 'scene_id',
                'open_id'              => 're_openid',
                'out_trade_no'         => 'mch_billno',
            ];
            Func::arrayReKey($this->data, $renameFields);
            return true;
        }
        return false;
    }

    /**
     * 执行/请求
     * @return array|bool
     */
    public function execute() {
        if ($this->validAndRefactorData()) {
            $this->data['wxappid'] = $this->config['public_app_id'];
            $this->data['mch_id'] = $this->config['public_mch_id'];
            $this->data['client_ip'] = Func::getClientIp();
            $this->data['send_name'] = $this->config['acc_name'];
            $this->data['total_num'] = 1;
            $this->data['total_amount'] = intval($this->data['total_fee']);
            $this->data['scene_id'] = "PRODUCT_{$this->data['scene_id']}";
            $fields = 'mch_billno,mch_id,wxappid,send_name,re_openid,total_amount,total_num,wishing,client_ip,act_name,remark,scene_id';
            $this->data = Func::arrayFilterKey($this->data, $fields);
            $certs = [
                'cert' => $this->config['pub_ssl_cert_path'],
                'key'  => $this->config['pub_ssl_key_path'],
            ];
            return $this->clientRequestExecute($this->data, $certs);
        }
        return false;
    }


    public function aopClientRequestExecuteCallback($result) {
        return [
            'out_trade_no' => $result['mch_billno'],
            'pay_time'     => time(),
            'trade_no'     => $result['send_listid'],
        ];
    }
}