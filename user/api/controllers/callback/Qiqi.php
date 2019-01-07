<?php
/**
 * 柒柒支付回调模板
 * Created by sublim Text3
 * User: lqh6249
 * Date: 2018/07/30
 * Time: 10:58
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Qiqi extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'QIQI';
    //商户处理后通知第三方接口响应信息
    protected $success = "0000"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'sign'; //签名参数
    protected $of = 'Msg'; //订单号参数
    protected $mf = 'OrderAmount'; //订单金额参数(实际支付金额)
    protected $vs = ['Msg','TimeEnd']; //参数签名字段必需参数
    protected $ks = '&key='; //参与签名字符串连接符

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
        $sdata = array(
            'Msg' => $data['Msg'],
            'OrderAmount' => $data['OrderAmount'],
            'OrderNo' => $data['OrderNo'],
            'TimeEnd' => $data['TimeEnd'],
            'key' => $key
        );
        $string = data_to_string($sdata);
        $v_sign = md5($string);
        //验证签名是否正确
        if (strtoupper($sign) <> strtoupper($v_sign))
        {
            $this->PM->online_erro($name, '签名验证失败:' . $sign);
            exit($this->error);
        }
    }
}
