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

class RedPack extends WxBaseStrategy
{
    /**
     * 校验数据
     * @return mixed
     */
    public function validAndRefactorData()
    {
        $fields = 'open_id,out_trade_no,wx_red_pack_wishing,wx_red_pack_act_name,wx_red_pack_remark,wx_red_pack_scene_id';
        if (Func::validParams($this->data, $fields)) {
            $renameFields = [
                'wx_red_pack_wishing'  => 'wishing',
                'wx_red_pack_act_name' => 'act_name',
                'wx_red_pack_remark'   => 'remark',
                'wx_red_pack_scene_id' => 'scene_id',
                'open_id'              => 're_openid',
                'mch_billno'           => 'out_trade_no',
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
    public function execute()
    {
        if ($this->validAndRefactorData()) {
            return $this->clientRequestExecute($this->data);
        }
        return false;
    }

    /**
     * 执行请求
     * @param $data
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $data['wxappid'] = $this->config['public_app_id'];
        $data['mch_id'] = $this->config['public_mch_id'];
        $data['nonce_str'] = Func::getNonceStr();
        $data['client_ip'] = Func::getClientIp();
        $data['send_name'] = $this->config['acc_name'];
        $data['total_num'] = 1;
        $data['total_amount'] = intval($data['total_fee']);
        $data['scene_id'] = "PRODUCT_{$data['scene_id']}";
        $data = Func::arrayFilterKey($data, 'nonce_str,mch_billno,mch_id,wxappid,send_name,re_openid,total_amount,total_num,wishing,client_ip,act_name,remark,scene_id');
        $data['sign'] = Func::sign($data, $this->config['public_key']);
        $xml = Func::toXml($data);
        $certs = [
            'cert' => $this->config['pub_ssl_cert_path'],
            'key'  => $this->config['pub_ssl_key_path'],
        ];
        $response = $this->postXmlCurl($xml, $url, $certs, $timeOut);
        if ($response) {
            $result = Func::xmlToArray($response);
            if (!empty($result['return_code']) && $result['return_code'] === 'SUCCESS' && !empty($result['result_code']) && $result['result_code'] === 'SUCCESS') {
                return [
                    'out_trade_no' => $result['mch_billno'],
                    'pay_time'     => time(),
                    'trade_no'     => $result['send_listid'],
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