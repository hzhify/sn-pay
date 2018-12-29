<?php

/**
 * @name Func
 * @description
 * @author hz
 * @time 2018/12/26 12:19
 */

namespace pay\util;

class Func
{
    public static function validParams($data, $fields)
    {
        $fields = self::arrayVal($fields);
        $err = Err::getInstance();
        $dic = require PAY_ROOT . '/config/dic.php';
        foreach ($fields as $k) {
            if (!isset($data[$k]) || ($data[$k] != 0 && empty($data[$k]))) {
                $err->add($dic[$k] . '不能为空', $k);
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
    public static function arrayFilterKey($array, $keys)
    {
        $keys = array_flip(self::arrayVal($keys));
        return array_intersect_key($array, $keys);
    }

    /**
     * 变量转换成数据
     * @param string|array $var
     * @param string $spliter 分割字符，逗号必定会被分割，默认分号也会被分割
     * @return array
     */
    public static function arrayVal($var, $spliter = ';')
    {
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
    public static function objectToArray($array)
    {
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

    public static function resData($data)
    {
        return ['code' => 200, 'msg' => '', 'data' => $data];
    }

    public static function resErr($msg = '', $code = 400, $field = '*')
    {
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
    public static function getErrInfo()
    {
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

}