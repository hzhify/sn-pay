<?php

/**
 * @name AliBaseStrategy
 * @description
 * @author houzhi
 * @time 2017/11/23 17:42
 */

namespace pay\ali;

use pay\BaseStrategy;
use pay\util\Func;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'AopSdk.php';

abstract class AliBaseStrategy implements BaseStrategy
{
    protected $config = [];

    protected $data = [];

    public function __construct($data, $config)
    {
        $this->data = $data;
        $this->config = $config;
    }

    public function handle()
    {
        if ($this->checkConf()) {
            return $this->execute();
        }
        return false;
    }

    abstract function execute();

    public function checkConf()
    {
        $fields = ['gateway_url', 'app_id', 'partner', 'seller_email', 'rsa_private_key', 'rsa_public_key', 'alipay_public_key', 'gateway_url', 'md5_key'];
        if (Func::validParams($this->config, $fields)) {
            !isset($this->config['charset']) && $this->config['charset'] = 'UTF-8';
            !isset($this->config['sign_type']) && $this->config['sign_type'] = 'RSA2';
            !isset($this->config['return_data_format']) && $this->config['charset'] = 'json';
            return true;
        }
        return false;
    }

    /**
     * 验签方法
     * @param array $data 验签支付宝返回的信息，使用支付宝公钥。
     * @return bool
     */
    public function checkSign($data)
    {
        $aop = new \AopClient();
        $aop->alipayPublicKey = $this->config['alipay_public_key'];
        $result = $aop->rsaCheckV1($data, $this->config['alipay_public_key'], $this->config['sign_type']);
        return $result;
    }


    /**
     * 发起转账请求
     * @param object $request 请求实例
     * @param string $func 方法
     * @return \AopClient
     */
    public function aopClientRequestExecute($request, $func)
    {
        $aop = new \AopClient();
        $aop->gatewayUrl = $this->config['gateway_url'];
        $aop->appId = $this->config['app_id'];
        $aop->rsaPrivateKeyFilePath = $this->config['rsa_private_key'];
        $aop->alipayPublicKey = $this->config['alipay_public_key'];
        $aop->apiVersion = "1.0";
        $aop->postCharset = $this->config['charset'];
        $aop->format = $this->config['return_data_format'];
        $aop->signType = $this->config['sign_type'];
        $result = $aop->$func($request);
        if (method_exists($this, 'aopClientRequestExecuteCallback')) {
            return call_user_func([$this, 'aopClientRequestExecuteCallback'], $result);
        }
        return $result;
    }

}