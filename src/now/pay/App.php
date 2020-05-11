<?php

/**
 * @name App
 * @description APP支付
 * @author houzhi
 * @time 2019/09/27 19:50
 */

namespace pay\now\pay;

class App extends NowPayBaseStrategy {

    protected $payType = 'app';

    /**
     * @see 文档地址（手机APP） https://nc.ipaynow.cn/s/xDGNR56JJK78m9e
     * @return bool|string
     */
    public function handle() {
        return parent::handle();
    }
}