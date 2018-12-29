<?php

/**
 * @name SendRedPack
 * @description 微信发红包
 * @author houzhi
 * @time 2018/11/19 12:14
 */

namespace vApp\lib\src\wx\trans;

use v;
use vApp;

class SendRedPack extends vApp\lib\src\wx\WxBaseStrategy {

    protected $config = [];

    protected $data = [];


    public function __construct(array $data, array $config) {
        $this->config = $config;
        $this->data = $data;
    }

    /**
     * 校验请求参数
     * @return bool
     */
    private function validData() {
        if (vApp\lib\service\Verification::validParams('open_id,out_trade_no,wx_red_pack_wishing,wx_red_pack_act_name,wx_red_pack_remark,wx_red_pack_scene_id')) {
            $renameFields = [
                'wx_red_pack_wishing' => 'wishing',
                'wx_red_pack_act_name' => 'act_name',
                'wx_red_pack_remark' => 'remark',
                'wx_red_pack_scene_id' => 'scene_id',
            ];
            array_rekey($this->data, $renameFields);
            return true;
        }
        return false;
    }

    /**
     * 保存请求数据
     * @return bool
     */
    private function saveData() {
        $model = v\App::model('Payment');
        if ($item = $model->getByOutTradeNo($this->data['out_trade_no'])) {
            if ($item['status'] === 1) {
                v\Err::add('已转账成功');
                return false;
            }
            $this->data['req_time'] = time();
            $this->data['_id'] = $item['_id'];
            return $model->setData($this->data)->subData($item)->isValid() && $model->upByID($item['_id']);
        } else {
            $this->data['extend'] = array_filter_key($this->data, 'wishing,act_name,remark,scene_id'); // 发红包的扩展数据
            if ($model->setData($this->data)->isMust() && $model->addOne()) {
                $this->data['_id'] = $model->lastID();
                return true;
            }
        }
        return false;
    }

    /**
     * 执行/请求
     * @return bool
     */
    public function handle() {
        if ($this->validData() && $this->saveData()) {
            $id = $this->data['_id']; //获取数据记录ID
            $result = $this->clientRequestExecute($this->data);
            if ($result) {
                $payInfo = [
                    'out_trade_no' => $result['mch_billno'],
                    'pay_time' => time(),
                    'trade_no' => $result['send_listid'],
                ];
                v\App::model('Payment')->paySuccess($payInfo, $id, 'wechat');
                $fields = ['out_trade_no', 'subject', 'total_fee', 'trade_type', 'pay_way', 'mch_id', 'pay_time', 'trade_no', 'remark', 'open_id'];
                $return = v\App::model('Payment')->getByID($id, ['field' => $fields]);
                unset($return['_id']);
                return $return;
            }
        }
        return false;
    }

    /**
     * 执行请求
     * @param $data
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $timeOut = 6) {
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        $data['wxappid'] = $this->config['public_app_id'];
        $data['mch_id'] = $this->config['public_mch_id'];
        $data['nonce_str'] = vApp\lib\Extension::getNonceStr();
        $data['client_ip'] = vApp\lib\Extension::getClientIp();
        $data['send_name'] = $this->config['acc_name'];
        $data['re_openid'] = $data['open_id'];
        $data['total_num'] = 1;
        $data['total_amount'] = intval($data['total_fee']);
        $data['mch_billno'] = $data['out_trade_no'];
        $data['scene_id'] = "PRODUCT_{$data['scene_id']}";
        $data = array_filter_key($data, 'nonce_str,mch_billno,mch_id,wxappid,send_name,re_openid,total_amount,total_num,wishing,client_ip,act_name,remark,scene_id');
        $data['sign'] = vApp\lib\Extension::sign($data, $this->config['public_key']);
        $xml = vApp\lib\Extension::toXml($data);
        $certs = [
            'cert' => $this->config['pub_ssl_cert_path'],
            'key' => $this->config['pub_ssl_key_path'],
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