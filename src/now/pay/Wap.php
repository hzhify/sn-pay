<?php

/**
 * @name Wap
 * @description 网页（H5）支付
 * @author houzhi
 * @time 2019/09/27 14:18
 */

namespace pay\now\pay;

use pay\util\Func;
use pay\util\Err;

class Wap extends NowPayBaseStrategy {

    protected $payType = 'wap';

    public function handle() {
        $reqParamStr = parent::handle();
        $resStr = $this->postCurl($this->config['gateway_url'], $reqParamStr);
        Func::log('now-wap-' . $resStr, $this->logFile);
        $err = Err::getInstance();
        if (empty($resStr)) {
            $err->add('支付请求失败');
            return false;
        }
        $res = [];
        parse_str($resStr, $res);
        if (!empty($res) && isset($res['responseCode']) && $res['responseCode'] == 'A001' && !empty($res['tn'])) {
            $url = [
                'pay_url'    => urldecode($res['tn']),
                'return_url' => "{$this->data['frontNotifyUrl']}?out_trade_no={$this->data['mhtOrderNo']}"
            ];
            if (empty($this->data['frontNotifyUrl'])) {
                $url['return_url'] = "{$this->config['return_url']}?out_trade_no={$this->data['mhtOrderNo']}";
            } else {
                $conStr = strrpos($this->data['frontNotifyUrl'], '?') == false ? '?' : '&';
                $url['return_url'] = "{$this->data['frontNotifyUrl']}{$conStr}out_trade_no={$this->data['mhtOrderNo']}";
            }
            return $this->getHtml($url);
        }
        return false;
    }

    public function validAndRefactorData() {
        $data = parent::validAndRefactorData();
        $data['outputType'] = 2;
        return $data;
    }

    public function getHtml($url) {
        return '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"/><meta http-equiv="X-UA-Compatible" content="ie=edge"/><style>html,body,div,p,span,h4 {padding: 0;margin: 0;}html,body,.wrapper {height: 100%;overflow: hidden;}.wrapper {background-color: rgba(0, 0, 0, 0.5);}.dialog {width: 3.06rem;background-color: #fff;border-radius: 0.06rem;padding-top: 0.24rem;margin: 1.62rem auto 0;text-align: center;overflow: hidden;}.dialog h4 {font-size: 0.16rem;line-height: 0.22rem;font-weight: normal;color: #333;margin-bottom: 0.14rem;}.dialog p {line-height: 0.2rem;font-size: 0.14rem;color: #666;}.dialog .button {margin-top: 0.25rem;display: flex;font-size: 0.16rem;color: #333;line-height: 0.5rem;border-top: 0.01rem solid #c8c8c8;}.dialog .button span {flex: 1;border-right: 0.01rem solid #c8c8c8;}.dialog .button span:last-child {border-right: none;}.dialog .button span.confirm {color: #1A89FA;}</style></head><body><div class=\'wrapper\'><div class=\'dialog\'><h4>支付确认</h4><p>请点击“去支付”，进入支付宝完成支付</p><p>如果您已支付，请点击“已支付”</p><div class=\'button\'><span class=\'cancel\'>已支付</span><span class=\'confirm\'>去支付</span></div></div></div><a id="payUrl" style="display: none" href="' . $url['pay_url'] . '"></a></body><script>var height = document.documentElement.clientWidth || document.body.clientWidth;document.documentElement.style.fontSize = height / 3.75 + \'px\';var cancelDom = document.querySelector(\'.cancel\'),confirmDom = document.querySelector(\'.confirm\');cancelDom.onclick = function () {window.location.href = "' . $url['return_url'] . '";};confirmDom.onclick = function () {document.getElementById("payUrl").click();};setTimeout(function () {document.getElementById("payUrl").click();}, 1000);</script></html>';
    }

}