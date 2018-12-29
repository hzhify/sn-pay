<?php

/**
 * @name WxBaseStrategy
 * @description
 * @author houzhi
 * @time 2017/11/26 12:55
 */

namespace vApp\lib\src\wx;

use v;
use vApp;
use vApp\lib\src\common\BaseStrategy;

abstract class WxBaseStrategy implements BaseStrategy {

    protected $config = [];

    abstract function handle();

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
            v\Err::add("curl出错，错误码:$error");
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
     * @param int $timeOut
     * @return array|bool|mixed
     */
    public function clientRequestExecute($data, $timeOut = 6) {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data['appid'] = $this->config['app_id'];
        $data['mch_id'] = $this->config['mch_id'];
        $signKey = $this->config['md5_key'];
        if ($data['trade_type'] === 'JSAPI') {
            $data['appid'] = $this->config['public_app_id'];
            $signKey = $this->config['public_key'];
            $data['mch_id'] = $this->config['public_mch_id'];
        }

        $data['nonce_str'] = vApp\lib\Extension::getNonceStr();
        $data['notify_url'] = $this->config['notify_url'];
        if (empty($data['spbill_create_ip'])) {
            $data['spbill_create_ip'] = vApp\lib\Extension::getClientIp();
        }
        v\App::log($data, 'test.log');
        $data['sign'] = vApp\lib\Extension::sign($data, $signKey);
        $xml = vApp\lib\Extension::toXml($data);
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        if ($response) {
            $result = vApp\lib\Extension::xmlToArray($response);
            v\App::log($result, 'test.log');
            $flag = !empty($result['return_code']) && $result['return_code'] === 'SUCCESS' && !empty($result['result_code']) && $result['result_code'] === 'SUCCESS';
            if ($flag) {
                if (!vApp\lib\Extension::checkSign($result, $signKey)) {
                    v\Err::add('签名错误');
                    return false;
                }
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
