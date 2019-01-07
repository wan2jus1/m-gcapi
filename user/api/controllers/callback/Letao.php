<?php
/**
 * 乐淘支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/06/03
 * Time: 11:14
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Letao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'LETAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'Signature'; //签名参数
    protected $of = 'TxSN'; //订单号参数
    protected $mf = 'Amount'; //订单金额参数(实际支付金额)
    protected $vm = '1';//是否验证金额 
    protected $vt = 'fen';//金额单位 
    protected $tf = 'Status'; //支付状态参数字段名
    protected $tc = '1'; //支付状态成功的值

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名 
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        unset($data['SignMethod']);
        ksort($data);
        //把数组参数以key=value形式拼接最后加上$key值 空值也参与签名
        $sign_string = data_to_string($data) . $key;
        $v_sign = md5($sign_string);
        if ($sign <> $v_sign)
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
