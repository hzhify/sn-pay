<?php

/**
 * @name BaiduPayBaseStrategy
 * @description 百度支付
 * @author houzhi
 * @time 2018/10/22 14:24
 */

namespace vApp\lib\src\baidu\pay;

use v;
use vApp;

abstract class BaiduPayBaseStrategy extends vApp\lib\src\baidu\BaiduBaseStrategy {

    protected $config = [];

    protected $data = [];

    public function __construct($data, $config) {
        $this->data = $data;
        $this->config = $config;
    }

    public function handle() {
        if ($this->validData() && $this->saveData()) {
            return array_filter_key($this->data, 'body,subject,out_trade_no,time_expire,total_amount');
        }
        return false;
    }

    /**
     * 校验数据
     * @return bool
     */
    public function validData() {
        if (empty($this->data['subject'])) {
            v\Err::add('交易标题不能为空', 'subject');
            return false;
        }
        if (empty($this->data['total_fee'])) {
            v\Err::add('交易金额不能为空', 'total_fee');
            return false;
        }

        if (empty($this->data['notify_url'])) {
            v\Err::add('异步回调通知地址不能为空', 'notify_url');
            return false;
        }
        return true;
    }

    /**
     * 保存数据
     * @return bool
     */
    public function saveData() {
        $model = v\App::model('Payment');
        $item = $model->getByOutTradeNo($this->data['out_trade_no']);
        //如果支付信息不存在，则添加支付休息
        if (empty($item)) {
            $rs = $model->setData($this->data)->isMust() && $model->addOne();
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
            $rs = $model->setData($this->data)->subData($item)->isValid() && $model->upByID($item['_id']);
        }
        return $rs;
    }

}