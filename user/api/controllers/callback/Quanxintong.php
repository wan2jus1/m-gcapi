<?php

defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once __DIR__.'/Publicpay.php';

class Quanxintong extends Publicpay
{
    //redis错误标识名称
    protected $r_name = 'Quanxintong';
    //商户处理后通知第三方接口响应信息
    protected $success = "success"; //成功响应
    //异步返回必需验证参数
    protected $sf = 'SIGN'; //签名参数
    protected $of = 'ORDER_ID'; //订单号参数
    protected $mf = 'ORDER_AMT'; //订单金额参数(实际支付金额)
    protected $tf = 'TRANS_STATUS'; //支付状态参数字段名
    protected $tc = 'success'; //支付状态成功的值
    protected $bu = 'ID'; //支付状态成功的值
    protected $vo = 0; //订单号参数是否直接获取订单号

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 第三方支付异步回调接口验证
     * @return true
     */
    public function callbackurl()
    {
        //redis记录支付错误信息标识
        $name = $this->r_name;
        //获取异步接口返回的数据（数组形式）
        $sf = $this->sf; //签名字段
        $of = $this->of; //订单号字段
        $mf = $this->mf; //订单金额字段(实际支付金额)
        //获取异步返回的参数
        $data = $this->getReturnData();
        //验证参与签名参数是否都存在
        if (!empty($this->vs))
        {
            //返回不存在的签名参数
            $ds = array_diff($this->vs,array_keys($data));
            if (!empty($ds))
            {
                $pp = implode(',',$ds);
                $msg = "缺少验证签名参数：{$pp}";
                $this->PM->online_erro($name,$msg);
                exit($this->error);
            }
            unset($ds,$pp,$msg);
        }
        //获取订单金额、订单号、签名等数据
        $sign = $data[$sf]; //签名
        $money = $data[$mf]; //实际支付金额（可能跟订单金额不符）
        /*
         *@备注：部分第三返回订单号包含订单号和商户号信息需要截取处理
         *@默认 订单号直接从返回参数中获取
         *@其他 订单号需要处理后的数据方为订单号(此时商户号字段为必须验证字段)
         */
        //订单号中含有商户信息需要处理
        if (isset($this->vo) && (1 <> $this->vo))
        {
            $md = $this->bu;
            $or = $data[$of]; //包含有商户号的订单
            $order_num = str_replace($md,'',$or);
            //直接以订单好参数获取点订单号
        } else {
            $order_num = $data[$of]; //返回的订单号
        }
        //对需要处理的订单加锁
        $bool = $this->PM->fbs_lock('temp:new_order_num' . $order_num);
        if (!$bool) exit('正在处理,请稍后');
        //根据订单号获取配置信息
        $pay = $this->PM->order_detail($order_num);
        if (empty($pay))
        {
            $msg = "无效的订单号:{$order_num}";
            $this->PM->online_erro($name, $msg);
            exit($this->error);
        }
        //验证支付状态
        if (!empty($this->tf))
        {
            if (!isset($data[$this->tf]))
            {
                $msg = "缺少支付状态参数:{$this->tf}";
                $this->PM->online_erro($name, $msg);
                exit($this->error);
            }
            /* 备注说明
            @ 比较返回的成功状态参数值是否与设定一致 (忽略大小写)
            @ 如果设定支付成值只有一个,直接判断与设定值是否一致
            @ 如果设定支付成值有多个,判断当前状态值是否为设定值其中之一
            @ 结果不一致且不为设定值之一 则认为支付失败
             */
            $tc = $this->tc;
            $vc = $data[$this->tf];
            //如果支付成功的参数值不止一个($this->tc)是一个数组
            if (is_array($tc))
            {
                foreach($tc as $key => $val)
                {
                    $tc[$key] = strtoupper($val);
                }
                //判断支付状态值是否为规定的支付成功的值中的一个
                if (!in_array(strtoupper($vc), $tc))
                {
                    $this->PM->online_erro($name, '交易失败:未成功支付');
                    exit($this->error);
                }
            } else {
                //如果只有一个状态值 比较是否相同
                if (strtoupper($tc) <> strtoupper($vc))
                {
                    $this->PM->online_erro($name, '交易失败:未成功支付');
                    exit($this->error);
                }
            }
            unset($vc,$tc);
        }
        //验证签名（部分第三方订单金额和实际支付金额不一致）
        //是否需要用户商户号参与签名计算 且返回信息中不包含商户ID信息
        if (isset($this->vd) && (1 == $this->vd))
        {
            //用户商户号参与签名计算
            $this->verifySign($data,$pay,$name);
        } else {
            $this->verifySign($data,$pay[$this->pk],$name);
        }
        /*
        * 验证金额部分 三种验证方式
        * @1 金额单位类型为元 (默认方式)
        * @2 金额单位类型为分 (部分第三方使用单位)
        * @3 金额不一致 更新金额为实际支付金额
         */
        //如果不严格验证订单金额（更新金额为实际支付金额）
        if (!isset($this->vm) || (1 <> $this->vm))
        {
            //以分为单位金额需要转换成元
            if('fen' == $this->vt) $money = fen_to_yuan($money);
            //不验证的金额数据 需实际比较返回的金额与实际金额差距
            $ap = abs($pay['price'] - $money);
            if (1 < $ap)
            {
                $msg = "返回金额{$money}与订单金额{$pay['price']}差距过大";
                $this->PM->online_erro($name,$msg);
                exit($this->error);
            }
            unset($ap);
            $pay['price'] = $money;
            $pay['total_price'] = ($money + $pay['discount_price']);
        } else {
            $this->verifyPrice($pay,$money,$name);
        }
        //判断订单状态是否已经处理
        if (1 <> $pay['status']) exit($this->success);
        //更新订单信息及用户余额
        $bool = $this->PM->update_order($pay);
        if ($bool) exit($this->success);
        $this->PM->online_erro($name, '写入现金记录失败:');
        exit('加钱失败');
    }
    protected function verifySign($data, $key, $name){
        $sign = $data[$this->sf];
        $vSignStr = $data['ORDER_ID'].$data['ORDER_AMT'].$data['BUS_CODE'];
        $vSignMd5 = md5($vSignStr);
        $vSignMd5Str = md5($vSignMd5.$key);
        $vSign = substr($vSignMd5Str,8,16);

        if ($sign != $vSign){
            $msg = "签名验证失败:{$sign}";
            $this->PM->online_erro($name,$msg);
            exit($this->error);
        }
    }
}