<?php

/**
 * @name WxBaseStrategy
 * @description
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace pay\wx;

use pay\BaseStrategy;
use pay\util\Func;
use pay\util\Err;

abstract class WxBaseStrategy implements BaseStrategy {

    /**
     * 配置
     * @var array
     */
    protected $config = [];

    /**
     * 请求参数
     * @var array
     */
    protected $data = [];

    /**
     * 错误对象
     * @var object|Err
     */
    protected $err;

    /**
     * 日志文件名称
     * @var string
     */
    protected $logFile;

    /**
     * 接口网关地址
     * @var string
     */
    protected $gatewayUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';

    /**
     * 请求是否使用证书
     * @var bool
     */
    protected $isUseCert = false;

    /**
     * 是否是公众号进行支付
     * @var bool
     */
    protected $isPubPay = false;

    protected $checkSign = false;

    public function __construct($data, $config) {
        $this->data = $data;
        $this->config = $config;
        $this->err = Err::getInstance();
        $this->logFile = 'pay-' . date('Ymd') . '.log';
    }

    public function handle() {
        if ($this->checkConf()) {
            return $this->execute();
        }
        return false;
    }

    abstract function execute();

    public function checkConf() {
        $fields = ['app_id', 'app_secret', 'mch_id', 'md5_key', 'ssl_cert_path', 'ssl_key_path'];
        return Func::validParams($this->config, $fields);
    }

    /**
     * 验签方法
     * @param array $data 验证签名。
     * @param string $signKey
     * @return bool
     */
    public function checkSign($data, $signKey) {
        return isset($data['sign']) && Func::sign($data, $signKey, ['sign', 'sign_type']);
    }

    /**
     * @param $xml
     * @param $url
     * @param array $certs
     * @param int $second
     * @return mixed
     */
    public function postXmlCurl($xml, $url, $certs = [], $second = 30) {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //如果有配置代理这里就设置代理
        if ($this->config['curl_proxy_host'] != "0.0.0.0" && $this->config['curl_proxy_port'] != 0) {
            curl_setopt($ch, CURLOPT_PROXY, $this->config['curl_proxy_host']);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->config['curl_proxy_port']);
        }
        //IPv6支持
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        if (!empty($certs)) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $certs['cert']);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $certs['key']);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            $this->err->add("curl出错，错误码:$error");
            return false;
        }
    }

    /**
     * 获取毫秒级别的时间戳
     */
    public static function getMilliSecond() {
        //获取毫秒的时间戳
        $time = explode(" ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode(".", $time);
        $time = $time2[0];
        return $time;
    }


    /**
     * 执行请求
     * @param $data
     * @param array $certs
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $certs = [], $timeOut = 6) {
        $signKey = $this->isPubPay ? $this->config['public_key'] : $this->config['md5_key'];
        $data['nonce_str'] = Func::getNonceStr();
        $data['sign'] = Func::sign($data, $signKey);
        $xml = Func::toXml($data);
        if ($response = $this->postXmlCurl($xml, $this->gatewayUrl, $certs, $timeOut)) {
            $result = Func::xmlToArray($response);
            if (!empty($result['return_code']) && $result['return_code'] === 'SUCCESS' && !empty($result['result_code']) && $result['result_code'] === 'SUCCESS') {
                if (!$this->checkSign || Func::checkSign($result, $signKey)) { // 校验签名
                    if (method_exists($this, 'aopClientRequestExecuteCallback')) {
                        return call_user_func([$this, 'aopClientRequestExecuteCallback'], $result);
                    }
                    return $result;
                }
                $this->err->add('签名错误');
            } else {
                $errCode = empty($result['err_code']) ? $result['return_code'] : $result['err_code'];
                $errMsg = empty($result['err_code_des']) ? $result['return_msg'] : $result['err_code_des'];
                $this->err->add($errMsg, '*', $errCode);
            }
        } else {
            $this->err->add('请求失败');
        }

        return false;
    }

}
