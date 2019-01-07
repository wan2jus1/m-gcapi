<?php
/**
 * 財富寶支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/02
 * Time: 12:36
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Caifubao extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'CAIFUBAO';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'order'; //订单号参数
    protected $vd = 1; //是否使用用户商户号信息
    protected $mf = 'money'; //订单金额参数(实际支付金额)
    protected $tf = 'status'; //支付状态参数字段名
    protected $tc = '100'; //支付状态成功的值
    protected $vs = ['time','type','uid','paytime'];
    protected $ks = '#KEY='; //参与签名字符串连接符

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
    protected function verifySign($data,$pay,$name)
    {
        //获取签名字段并删除不参与签名字段
        $sign = $data[$this->sf];
        unset($data[$this->sf]);
        //获取签名字符串
        $k = $this->ks . $pay['pay_server_num'];
        $sign_data = array(
            'order' => $data['order'],
            'time' => $data['time'],
            'money' => $data['money'],
            'type' => $data['type'],
            'uid' => $data['uid'],
            'paytime' => $data['paytime'],
            'status' => $data['status']
        ); 
        $string = data_to_string($sign_data) . $k;
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
