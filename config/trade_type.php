<?php

/**
 * @name ${NAME}
 * @description
 * @author hz
 * @time 2018/12/26 11:06
 */
return [
    'ali.pay.page', //支付宝电脑网站支付
    'ali.pay.wap', //支付宝手机网页支付
    'ali.pay.app', //支付宝APP支付
    'ali.pay.qr', //支付宝扫码支付
    'ali.trans.trans', //支付宝单笔转账到个人账户
    'ali.trans.query', //支付宝单笔转账到个人账户（订单查询）
    'ali.notify.notify', // 支付宝回调
    'ali.refund.refund', // 支付宝退款
    'ali.refund.query', // 支付宝查询退款

//    ------
    'wx.pay.pub', // 微信公众号支付
    'wx.pay.qr', // 微信原生扫码
    'wx.pay.wap', // 微信手机网页
    'wx.pay.app', // 微信app
    'wx.pay.mini', // 微信小程序
    'wx.trans.trans', // 微信转账（企业付款到零钱）
    'wx.trans.red_pack', // 微信红包
    'wx.notify.notify', // 微信支付回调
    'wx.refund.refund', // 微信退款
    'wx.refund.query', // 微信退款查询
    'wx.notify.refund', // 微信退款回调

    //----
    'bd.pay.mini_app', // 百度小程序支付
    'bd.notify.notify', // 支付宝回调
];
