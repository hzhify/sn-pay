<?php

/**
 * @name AliBaseStrategy
 * @description
 * @author houzhi
 * @time 2017/11/23 17:42
 */

namespace vApp\lib\src\baidu;

use v;
use vApp;
use vApp\lib\src\common\BaseStrategy;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'Autoloader.php';

abstract class BaiduBaseStrategy implements BaseStrategy {

    protected $config = [];

    abstract function handle();


    /**
     * 验签方法
     * @param array $data
     * @return bool
     */
    public function check($data) {
        $rsaPublicKeyStr = file_get_contents($this->config['public_key']);
        return \NuomiRsaSign::checkSignWithRsa($data, $rsaPublicKeyStr);
    }

    public function getSign($data) {
        return \NuomiRsaSign::genSignWithRsa($data, file_get_contents($this->config['rsa_private_key']));
    }

}