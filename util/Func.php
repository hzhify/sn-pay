<?php

/**
 * @name Func
 * @description
 * @author hz
 * @time 2018/12/26 12:19
 */

namespace pay\util;

class Func {
    public static function validParams($data, $fields) {
        $fields = self::arrayVal($fields);
        $err = Err::getInstance();
        $dic = require PAY_ROOT . '/config/dic.php';
        foreach ($fields as $k) {
            if (!isset($data[$k]) || ($data[$k] != 0 && empty($data[$k]))) {
                $err->add(($dic[$k] ?? $k) . '不能为空', $k);
                return false;
            }
        }
        return $data;
    }

    /**
     * 按key取得数组中的数据
     * @param array $array
     * @param array | string $keys
     * @return array
     */
    public static function arrayFilterKey($array, $keys) {
        $keys = array_flip(self::arrayVal($keys));
        return array_intersect_key($array, $keys);
    }

    /**
     * 变量转换成数据
     * @param string|array $var
     * @param string $spliter 分割字符，逗号必定会被分割，默认分号也会被分割
     * @return array
     */
    public static function arrayVal($var, $spliter = ';') {
        if (empty($var))
            return [];
        switch (gettype($var)) {
            case 'string':
                $var = strtr($var, [', ' => ',', "$spliter, " => ',', "$spliter" => ',']);
                $var = explode(',', trim($var, ', '));
                break;
            case 'array':
                $var = array_values($var);
                break;
            default :
                $var = [$var];
        }
        return $var;
    }

    /**
     * 将对象转成数组
     * @param $array
     * @return array
     */
    public static function objectToArray($array) {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = self::objectToArray($value);
            }
        }
        return $array;
    }

    public static function resData($data) {
        return ['code' => 200, 'msg' => '', 'data' => $data];
    }

    public static function resErr($msg = '', $code = 400, $field = '*') {
        $msgInfo = self::getErrInfo();
        $msg = $msgInfo['msg'] ? $msgInfo['msg'] : $msg;
        $code = $msgInfo['code'] === 400 ? $code : $msgInfo['code'];
        $field = $msgInfo['field'] === '*' ? $field : $msgInfo['field'];
        $data = null;
        $msg = compact('msg', 'code', 'field', 'data');
        $msg['code'] = strtoupper($msg['code']);
        return $msg;
    }

    /**
     * 获取错误信息
     * @return array
     */
    public static function getErrInfo() {
        $err = Err::getInstance()->get();
        $field = key($err['message']);
        if ($field) {
            $msg = $err['message'][$field];
            $code = $err['errNo'][0];
        } else {
            $field = '*';
            $msg = '';
            $code = 400;
        }
        return ['field' => $field, 'msg' => $msg, 'code' => $code];
    }

    /**
     * 签名
     * @param array $data 签名的数据
     * @param string $key 签名的key
     * @param array $filterFields 过滤的字段
     * @param bool $filterNull 是否过滤空值的字段
     * @return string
     *
     */
    public static function sign($data, $key, $filterFields = null, $filterNull = true) {
        $str = self::toUrlParams($data, $filterFields, $filterNull);
        return strtoupper(md5($str . '&key=' . $key));
    }

    /**
     * 参数数组转换为url参数
     * @param array $arr
     * @param array $filterFields 要过滤的字段
     * @param bool $filterNull 是否过滤空字段
     * @return string
     */
    public static function toUrlParams($arr, $filterFields = null, $filterNull = true) {
        $buff = "";
        if (is_array($arr)) {
            ksort($arr);
            foreach ($arr as $k => $v) {
                if (!((!empty($filterFields) && in_array($k, $filterFields)) || ($filterNull && empty($v)))) { //不在过滤数组内、开启过滤了空值，但是字段值不为空、没有开启过滤空值 这三种情况，字段才能进行加密
                    $buff .= $k . "=" . (is_array($v) ? json_encode($v) : $v) . "&"; //如果是数组，则先json_encode
                }
            }
            $buff = trim($buff, "&");
            //如果存在转义字符，那么去掉转义
            if (get_magic_quotes_gpc()) {
                $buff = stripslashes($buff);
            }
        }
        return $buff;
    }

    /**
     * 将数组转换成xml格式的数据
     * @param array $data
     * @return string
     */
    public static function toXml($data) {
        $str = '<xml>';
        foreach ($data as $key => $val) {
            if (is_numeric($val)) {
                $str .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $str .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $str .= '</xml>';
        return $str;
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string 产生的随机字符串
     */
    public static function getNonceStr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @return array
     */
    public static function xmlToArray($xml) {
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 检查签名
     * @param array $data
     * @param string $key
     * @return bool
     */
    public static function checkSign($data, $key) {
        return $data['sign'] === self::sign($data, $key, ['sign', 'sign_type']);
    }


    public static function getClientIp() {
        $ip = '127.0.0.1';
        if ($_SERVER['REMOTE_ADDR']) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        }
        return $ip;
    }

    /**
     * 生成二维码
     * @param mixed $data 生成二维码的数据
     * @param string $filename 二维码的名称
     * @param string $dir 路径
     * @return string
     */
    public static function getQrCode($data, $filename, $dir = '') {
        require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'phpqrcode.php';
        $level = 'QR_ECLEVEL_L'; // L-smallest, M, Q, H-best
        $size = 3; // 1-50
        $margin = 4;
        $dir === '' && $dir = PAY_ROOT . DIRECTORY_SEPARATOR . 'qrcode' . DIRECTORY_SEPARATOR . date("Y-m-d") . DIRECTORY_SEPARATOR;
        Func::log($dir, 'error.log');
        if (!is_dir($dir)) {
            Func::log($dir, 'error.log');
            mkdir($dir, 0777); // 使用最大权限0777创建文件
        }
        $file = $dir . $filename . '.png';
        QRcode::png($data, $file, $level, $size, $margin, true);
        return basename($file);
    }


    /**
     * 数组KEY重命名
     * @param array $array
     * @param array $keys
     * @return array
     */
    public static function arrayReKey(&$array, $keys) {
        foreach ($keys as $key => $keyNew) {
            if (isset($array[$key])) {
                $array[$keyNew] = $array[$key];
                unset($array[$key]);
            }
        }
        return $array;
    }

    public static function log($data, $file = 'default.log') {
        // 日志不进行/与中文的编码，日志之间空一行处理
        $data = date('Y-m-d H:i:s') . "\t" . (is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $data) . "\r\n\r\n";
        $logDir = PAY_ROOT . DIRECTORY_SEPARATOR . 'logs/';
        $filename = "$logDir/$file";
        @file_put_autodir($filename, $data, FILE_APPEND | LOCK_EX);
    }

}