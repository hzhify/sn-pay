<?php

/**
 * @name WxPayBaseStrategy
 * @description 微信支付
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace vApp\lib\src\wx\pay;

use v;
use vApp;

abstract class WxPayBaseStrategy extends vApp\lib\src\wx\WxBaseStrategy {

    protected $config = [];
    protected $data = [];

    public function __construct(array $data, array $config) {
        $this->config = $config;
        $this->data = $data;
    }

    public function handle() {
        if ($this->validData() && $this->saveData()) {
            $this->setTradeType();
            $this->data = array_filter_key($this->data, 'body,out_trade_no,time_expire,time_start,total_fee,spbill_create_ip,trade_type,scene_info');
            return $this->clientRequestExecute($this->data); //发起支付请求
        }
        return false;
    }

    /**
     * 设置交易类型
     * @return mixed
     */
    abstract protected function setTradeType();

    /**
     * 校验数据
     * @return mixed
     */
    public function validData() {
        if (empty($this->data['subject'])) {
            v\Err::add('交易标题不能为空', 'subject');
            return false;
        }
        $this->data['body'] = $this->data['subject'];

        if (empty($this->data['total_fee'])) {
            v\Err::add('交易金额不能为空', 'total_fee');
            return false;
        }

        if (empty($this->data['notify_url'])) {
            v\Err::add('异步回调通知地址不能为空', 'notify_url');
            return false;
        }
        //如果默认超时时间为1天
        $timeout = empty($this->data['timeout']) || $this->data['timeout'] > 86400 || !is_numeric($this->data['timeout']) ? $this->config['timeout'] : $this->data['timeout'];
        $startTime = array_value($this->data, 'start_time', time());
        $this->data['time_start'] = date('YmdHis', $startTime);
        $this->data['time_expire'] = date('YmdHis', $startTime + $timeout);
        return true;
    }

    /**
     * 保存数据
     * @return mixed
     */
    public function saveData() {
        $model = v\App::model('Payment');
        $item = $model->getByOutTradeNo($this->data['out_trade_no']);
        //如果支付信息不存在，则添加支付休息
        if (empty($item)) {
            return $model->setData($this->data)->isMust() && $model->addOne();
        } else { //更新数据
            if ($item['status'] === 1) {
                v\Err::add('订单已经支付成功');
                return false;
            }
            if ($item['itime'] - time() >= $this->config['timeout']) {
                v\Err::add('订单已经超时，不能再次支付');
                return false;
            }
            $this->data['req_time'] = time();
            return $model->setData($this->data)->subData($item)->isValid() && $model->upByID($item['_id']);
        }
    }
}
