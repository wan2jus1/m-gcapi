<?php
/**
 * 亿汇宝支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 10:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Yihuibao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'YIHUIBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "OK"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'orderid'; //订单号参数
    protected $md = 'memberid';//第三方平台返回的商户号字段
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $tf = 'returncode'; //支付状态参数字段名
    protected $tc = '00'; //支付状态成功的值
    protected $vm = 0;//是否验证金额(部分第三方实际支付金额不一致)
    protected $ks = '&key='; //参与签名字符串连接符
    protected $mt = 'D'; //返回签名是否大写 D/X

    public function __construct()
    {
        parent::__construct();
    }
}
