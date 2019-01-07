<?php
/**
 * 百信支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Baixin_model extends Publicpay_model
{
    protected $c_name = 'baixin';
    private $p_name = 'BAIXIN';//商品名称

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
        return $this->buildForm($data);
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
        $string = ToUrlParams($data) . $this->key;
        $data['sign'] = md5($string);
        return $data;
    }

    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['value'] = $this->money; 
        $data['parter'] = $this->merId;//商户号
        $data['type'] = $this->getPayType();
        $data['orderid'] = $this->orderNum;
        $data['callbackurl'] = $this->callback;
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
                return 'WEIXIN';//微信扫码
                break;
            case 2:
                return 'WXWAP';//微信WAP
                break;
            case 4:
                return 'ALIPAY';//支付宝扫码
                break;
            case 5:
                return 'ALIWAP';//支付宝WAP
                break;
            case 7:
                return $this->bank_type;//网银支付
                break;
            case 8:
                return 'QQCODE';//QQ扫码
                break;
            case 9:
                return 'JINGDONG';//京东扫码
                break;
            case 12:
                return 'QQWAP';//QQwap
                break;
            case 13:
                return 'JDWAP';//京东wap
                break;
            case 17:
                return 'VISA';//银联扫码
                break;
            case 18:
                return 'VISAWAP';//银联WAP
                break;
            case 25:
                return 'KUAIJIE';//网银快捷
                break;
            default:
                return 'ALIPAY';//支付宝pc扫码
                break;
        }
    }
}
