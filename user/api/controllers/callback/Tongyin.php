<?php
/**
 * 同银支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 10:08
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Tongyin extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'TONGYIN';
    //商户处理后通知第三方接口响应信息
    protected $error = '{"responseCode":"0042","msg":"ERROR"}'; //错误响应
    protected $success = '{"responseCode":"0000"}'; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign_data'; //签名参数
    protected $of = 'order_id'; //订单号参数
    protected $mf = 'amount'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'trade_status'; //支付状态参数字段名
    protected $tc = '0'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名
     * @access protected
     * @param Array $data   回调参数数组
     * @param String $key 秘钥
     * @return boolean $name 错误标识
     */
    protected function verifySign($data,$key,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串 并验证签名
        ksort($data);
        $string = ToUrlParams($data) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
