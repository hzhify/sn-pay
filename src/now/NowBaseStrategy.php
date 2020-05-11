<?php

/**
 * @name NowBaseStrategy
 * @description
 * @author houzhi
 * @time 2019/09/27 14:18
 */

namespace pay\now;

use pay\BaseStrategy;
use pay\util\Func;

abstract class NowBaseStrategy implements BaseStrategy {
    protected $config = [];

    protected $data = [];

    protected $logFile;

    public function __construct($data, $config) {
        $this->data = $data;
        $this->config = $config;
        $this->logFile = 'pay-' . date('Ymd') . '.log';
    }

    /**
     * 将参数按照签名规则组装成字符串
     * @param $data
     * @param bool $decode
     * @return bool|string
     */
    public function getParamStr($data, $decode = false) {
        if (empty($data))
            return false;
        ksort($data);
        $str = '';
        foreach ($data as $k => $v) {
            if ($v == '' || $k == 'signature')
                continue;
            $str .= $k . '=' . ($decode ? urldecode($v) : $v) . '&';
        }
        return $str;
    }

    /**
     * 生成签名
     * @param $data
     * @param $key
     * @param bool $decode
     * @return bool|string
     */
    public function getSignStr($data, $key, $decode = false) {
        if ($str = $this->getParamStr($data, $decode)) {
            return strtolower(md5($str . md5($key)));
        }
        return false;
    }

    public function postCurl($url, $data) {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        //curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 1); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        //curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 40); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            return 'Errno' . curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据
    }
}