<?php
/**
 * 福汇通支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/08/29
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Fuhuitong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'FUHUITONG';
    //商户处理后通知第三方接口响应信息
    protected $success = "000000"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'tranNo'; //订单号参数
    protected $mf = 'tranAmt'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'status'; //支付状态参数字段名
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
        //获取签名字符串
        ksort($data);
        $string = data_to_string($data) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
