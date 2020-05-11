<?php

/**
 * @name Qr
 * @description 扫码支付
 * @author houzhi
 * @time 2019/09/27 19:50
 */

namespace pay\now\pay;

use pay\util\Err;
use pay\util\Func;

class Qr extends NowPayBaseStrategy {

    protected $payType = 'qr';

    public function handle() {
        $reqParamStr = parent::handle();
        $resStr = $this->postCurl($this->config['gateway_url'], $reqParamStr);
        $err = Err::getInstance();
        if (empty($resStr)) {
            $err->add('支付请求失败');
            return false;
        }
        $res = [];
        parse_str($resStr, $res);
        if (!empty($res) && isset($res['responseCode']) && $res['responseCode'] == 'A001' && !empty($res['tn'])) {
            $qrCodeDir = $this->data['qr_code_dir'] ?? '';
            $url = urldecode($res['tn']);
            return [
                'code_img' => Func::getQrCode($url, 'now_' . $res['mhtOrderNo'], $qrCodeDir),
                'code_url' => $url
            ];
        }
        return false;
    }


}
