<?php

/**
 * @name BdBaseStrategy
 * @description
 * @author houzhi
 * @time 2017/11/23 17:42
 */

namespace pay\bd;

use pay\BaseStrategy;
use pay\util\Err;
use pay\util\Func;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'sdk' . DIRECTORY_SEPARATOR . 'Autoloader.php';

abstract class BdBaseStrategy implements BaseStrategy {
    protected $config = [];

    protected $data = [];

    protected $err;

    public function __construct($data, $config) {
        $this->data = $data;
        $this->config = $config;
        $this->err = Err::getInstance();
    }

    public function handle() {
        if ($this->checkConf()) {
            return $this->execute();
        }
        return false;
    }

    abstract function execute();

    public function checkConf() {
        $fields = ['deal_id', 'app_key', 'rsa_private_key', 'rsa_public_key', 'public_key'];
        return Func::validParams($this->config, $fields);
    }


    /**
     * 验签方法
     * @param array $data
     * @return bool
     */
    public function checkSign($data) {
        $rsaPublicKeyStr = file_get_contents($this->config['public_key']);
        return \NuomiRsaSign::checkSignWithRsa($data, $rsaPublicKeyStr);
    }

    public function getSign($data) {
        return \NuomiRsaSign::genSignWithRsa($data, file_get_contents($this->config['rsa_private_key']));
    }

}