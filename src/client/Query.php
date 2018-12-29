<?php

/**
 * @name Query
 * @description
 * @author houzhi
 * @time 2017/11/25 17:00
 */

namespace vApp\lib\src\client;

//use vApp\lib\src\alipay\query\AliPayQuery;
use vApp\lib\src\alipay\query\AliTransQuery;


class Query {

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
     * @param array $data
     * @param array $conf
     * @return mixed
     */
    public static function run($data, $conf) {
        switch ($data['trade_type']) {
            case 'alipay.query.trans':
                $class = new AliTransQuery();
                break;
        }
        return $class->handle($data, $conf);
    }
}