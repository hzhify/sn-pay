<?php

/**
 * @name Client
 * @description
 * @author hz
 * @time 2018/12/26 11:05
 */

namespace pay;

use pay\util\Func;

define('PAY_ROOT', strtr(dirname(dirname(__FILE__)), array('\\' => '/')));
// auto loader
spl_autoload_register(function ($className) {
    if (substr($className, 0, 3) == 'pay') {
        $fileName = PAY_ROOT . '/src' . strtr(substr($className, 3), ['\\' => '/']) . '.php';
        if (is_file($fileName)) {
            require $fileName;
        }
    }
});

require PAY_ROOT . '/util/Err.php';
require PAY_ROOT . '/util/Func.php';

class Client
{
    public static function run($tradeType, $conf, $data = [])
    {
        Func::log($tradeType, 'test.log');
        Func::log($conf, 'test.log');
        Func::log($data, 'test.log');
        $tradeTypes = require dirname(__DIR__) . '/config/trade_type.php';
        if (!in_array($tradeType, $tradeTypes)) {
            return Func::resErr('交易类型不正确', '400', 'trade_type');
        }
        $tradeType = explode('.', $tradeType);
        $class = $tradeType[2];
        $class = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $class);
        $class = ucfirst($class);
        $obj = "pay\\{$tradeType[0]}\\{$tradeType[1]}\\{$class}";
        $class = new $obj($data, $conf);
        if ($payInfo = $class->handle()) {
            return Func::resData(['pay_info' => $payInfo]);
        }
        return Func::resErr();
    }
}
