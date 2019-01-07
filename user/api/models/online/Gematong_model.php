<?php
/**
 * 个码通支付接口调用
 * User: lqh
 * Date: 2018/07/22
 * Time: 10:02
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Gematong_model extends Publicpay_model
{
    protected $c_name = 'gematong';
    private $p_name = 'GEMATONG';//商品名称

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取前端返回数据 部分第三方支付不一样
     * @param array
     * @return array
     */
    protected function returnApiData($data)
    {
        return $this->buildWap($data);
    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造签名参数
        ksort($data);
        $string = ToUrlParams($data,'','') . $this->key;
        $data['sign'] = sha1($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['pay_type'] = $this->getPayType();
        $data['mch_id'] = $this->merId;//商户号
        $data['amount'] = intval($this->money);//金额
        $data['order_id'] = $this->orderNum;//订单号 唯一
        $data['version'] = 'v1';
        $data['cb_url'] = $this->callback;
        return $data;
    }

    /**
     * 根据code值获取支付方式
     * @param string code
     * @return string 支付方式 参数
     */
    private function getPayType()
    {
        switch ($this->code)
        {
            case 1:
            case 2:
                return 'wechat';//微信
                break;
            case 4:
            case 5:
                return 'alipay';//支付宝扫码
                break;
            default:
                return 'alipay';//微信扫码
                break;
        }
    }


    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //传递参数
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($this->url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        //接收参数为JSON格式 转化为数组
        $data = json_decode($data,true);  
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //判断是否下单成功
        if (empty($data['data']['pay_url'])) 
        {
            $msg = isset($data['msg']) ? $data['msg'] : "返回参数错误";
            $this->retMsg("下单失败：{$msg}");
        } 
        return $data['data']['pay_url'];
    }
}
