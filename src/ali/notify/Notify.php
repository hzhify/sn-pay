<?php

/**
 * @name Notify
 * @description 支付宝异步回调通知
 * @author houzhi
 * @time 2017/11/25 17:15
 */

namespace pay\ali\notify;

use pay\ali\AliBaseStrategy;

class Notify extends AliBaseStrategy
{
    public function execute()
    {
        return 'fail';
    }
}