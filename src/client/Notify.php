<?php

/**
 * @name Notify
 * @description
 * @author houzhi
 * @time 2017/11/25 17:00
 */

namespace vApp\lib\src\client;

use vApp\lib\src\alipay\notify\Notify;
use vApp\lib\src\wx\notify\Notify;
use vApp\lib\src\baidu\notify\Notify;


class Notify {

    /**
     * 支付实例
     * @var
     */
    protected static $instance;

    protected $class;

    private function __construct() {
    }

    protected static function getInstance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param string $way
     * @param array $conf
     * @return mixed
     */
    public static function run($way, $conf) {
        switch ($way) {
            case 'alipay':
                $class = new Notify($conf);
                break;
            case 'wechat':
                $class = new Notify($conf);
                break;
            case 'baidu':
                $class = new Notify($conf);
                break;
        }
        return $class->handle();
    }
}