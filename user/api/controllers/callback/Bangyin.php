<?php
/**
 * Created by PhpStorm.
 * User: 57207
 * Date: 2018/9/24
 * Time: 16:33
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';
class Bangyin extends Publicpay
{
//redis错误标识名称
    protected $r_name = 'BANGYIN';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    protected $sf = 'sign'; //签名参数
    protected $of = 'merOrderId'; //订单号参数
    protected $mf = 'transAmt'; //订单金额参数(实际支付金额)
    protected $vm = 1;//是否验证金额(部分第三方实际支付金额不一致)
    protected $vt = 'fen';//金额单位
    protected $tf = 'respCode'; //支付状态参数字段名
    protected $tc = '60006'; //支付状态成功的值
    protected $ks = '&'; //参与签名字符串连接符

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证签名 (默认验证签名方法,部分第三方不一样)
     * @access protected
     * @param Array $data 回调参数数组
     * @param String $key 秘钥
     * @param String $name 错误标识
     * @return boolean true
     */
    protected function verifySign($data,$key,$name)
    {
        // 获取签名字符串 并去除不参与加密参数
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        unset($data['merResv']);
        unset($data['respMsg']);
        //构造验证签名字符串
        ksort($data);
        $string = $this->ks . ToUrlParams($data) . $key;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}