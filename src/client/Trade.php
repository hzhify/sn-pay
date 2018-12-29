<?php

/**
 * @name Trade
 * @description
 * @author houzhi
 * @time 2017/11/22 22:23
 */

namespace vApp\lib\src\client;

use vApp\lib\src\alipay\pay\App;
use vApp\lib\src\alipay\pay\Wap;
use vApp\lib\src\alipay\pay\Page;
use vApp\lib\src\alipay\pay\Qr;
use vApp\lib\src\alipay\trans\AliTrans;
use vApp\lib\src\wx\pay\WxWapPay;
use vApp\lib\src\wx\pay\WxAppPay;
use vApp\lib\src\wx\pay\WxQrPay;
use vApp\lib\src\wx\pay\WxJsPay;
use vApp\lib\src\wx\trans\WxTrans;
use vApp\lib\src\wx\trans\SendRedPack;
use vApp\lib\src\baidu\pay\BaiduMiniAppPay;


class Trade {

    /**
     * 支付实例
     * @var
     */
    protected static $instance;

    protected $class;

    private function __construct() {
    }

    protected static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param array $data
     * @param array $conf
     * @return mixed
     */
    public static function run($data, $conf) {
        switch ($data['trade_type']) {
            case 'baidu.pay.mini_app':
                $class = new BaiduMiniAppPay($data, $conf);
                break;
            case 'alipay.pay.app':
                $class = new App($data, $conf);
                break;
            case 'alipay.pay.wap':
                $class = new Wap($data, $conf);
                break;
            case 'alipay.pay.page':
                $class = new Page($data, $conf);
                break;
            case 'alipay.pay.qr':
                $class = new Qr($data, $conf);
                break;
            case 'alipay.trans.transfer':
                $class = new AliTrans($data, $conf);
                break;
            case 'wechat.pay.wap':
                $class = new WxWapPay($data, $conf);
                break;
            case 'wechat.pay.qr':
                $class = new WxQrPay($data, $conf);
                break;
            case 'wechat.pay.app':
                $class = new WxAppPay($data, $conf);
                break;
            case 'wechat.pay.pub':
                $class = new WxJsPay($data, $conf);
                break;
            case 'wechat.trans.transfer':
                $class = new WxTrans($data, $conf);
                break;
            case 'wechat.trans.red_pack':
                $class = new SendRedPack($data, $conf);
                break;
        }
        return $class->handle();
    }
}