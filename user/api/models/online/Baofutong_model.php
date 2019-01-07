<?php
/**
 * K付宝支付接口调用
 * User: lqh
 * Date: 2018/07/10
 * Time: 17:45
 */
defined('BASEPATH') or exit('No direct script access allowed');
//引用公用文件
include_once  __DIR__.'/Publicpay_model.php';

class Baofutong_model extends Publicpay_model
{
    protected $c_name = 'baofutong';
    private $p_name = 'BAOFUTONG';//商品名称

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
        /*if (5 == $this->code)
        {
            return $this->buildWap($data);
        } else {
            return $this->buildForm($data);
        }*/
        return $this->buildScan($data);

    }

    /**
     * 构造支付参数+sign值
     * @return array
     */
    protected function getPayData()
    {
        //构造基本参数
        $data = $this->getBaseData();
        //构造MD5签名参数
        $sign = $this->getSign($this->key,$data);
        $data['sign'] = $sign;
        return $data;
    }



    /**
     * 构造支付基本参数
     * @return array
     */
    private function getBaseData()
    {
        $data['account_id'] = $this->merId;//商户号
        /*if (in_array($this->code,$this->wap_code))
        {
            $data['content_type'] = 'json';
        } else {
            $data['content_type'] = 'text';
        }*/
        $data['content_type'] = 'json';
        $data['thoroughfare'] = 'service_auto';
        $data['type'] = 2;
        $data['out_trade_no'] = $this->orderNum;
        $data['robin'] = '2';
        $data['keyId'] = $this->key;
        $data['amount'] = $this->money;
        $data['callback_url'] = $this->callback;
        $data['success_url'] = $this->returnUrl;
        $data['error_url'] = $this->returnUrl;
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
                return 'wechat_auto';//微信
                break;
            case 4:
            case 5:
                return 'alipay_auto';//支付宝
                break;
            default:
                return 'alipay_auto';//支付宝
                break;
        }
    }

    /**
     * 获取支付网关地址 部分接口地址不唯一
     * @param array $pay 支付参数
     * @return array
     */
    protected function getPayUrl($pay)
    {
        //此处只处理扫码地址 WAP类地址需要另行获取
        $pay_url = isset($pay['pay_url']) ? trim($pay['pay_url']) : '';
        if (in_array($this->code,[1,4]))
        {
            $pay_url .= '/gateway/index/checkpoint.do';
        }
        return $pay_url;
    }

    /**
     * 获取支付结果
     * @param $data 支付参数
     * @return return 二维码内容
     */
    protected function getPayResult($pay_data)
    {
        //1.获取支付链接
        $res_url = $this->url;
        //var_dump($pay_data);die;
        $pay_data = http_build_query($pay_data);
        $data = post_pay_data($res_url,$pay_data);
        if (empty($data)) $this->retMsg('接口返回信息错误！');
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('接口返回信息格式错误！');
        //确认下单是否成功
        if (empty($data['data']['qrcode']) && empty($data['data']['order_id'])) {
            $msg = isset($data['msg']) ? $data['msg'] : '返回信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        //如果含有支付链接信息 直接返回支付连接
        if (!empty($data['data']['qrcode'])) {
            return $data['data']['qrcode'];
        }
        //2.第一步没有支付连接 需要再次接口获取支付链接
        unset($pay_data);
        //根据第一步获取的 order_id (必有)获取支付参数
        //构造参数 (此接口只接受GET方式)
        $pay_data['content_type'] = 'json';
        $pay_data['id'] = $data['data']['order_id'];
        $pay_data = http_build_query($pay_data);
        //根据支付类型构造接口地址
        $res_url = $this->url."/gateway/pay/service.do?{$pay_data}";
        //传递参数 获取支付地址
        $data = post_pay_data($res_url,$pay_data);
        if (empty($data)) $this->retMsg('获取支付链接信息错误！');
        $data = json_decode($data,true);
        if (empty($data)) $this->retMsg('获取支付链接信息格式错误！');
        //确认是否正确返回支付连接
        if (empty($data['data']['qrcode']))
        {
            $msg = isset($data['msg']) ? $data['msg'] : '返回支付连链接信息错误';
            $this->retMsg("下单失败：{$msg}");
        }
        return $data['data']['qrcode'];
    }

    /**
     * 根据参数获取加密结果sign
     * @param string code
     * @return string sign
     */
    private function getSign($key_id, $data)
    {
        $data = md5(number_format($data['amount'],2) . $data['out_trade_no']);
        $key[] ="";
        $box[] ="";
        $pwd_length = strlen($key_id);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++)
        {
            $key[$i] = ord($key_id[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++)
        {
            $j = ($j + $box[$i] + $key[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        $cipher = '';
        for ($a = $j = $i = 0; $i < $data_length; $i++)
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;

            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;

            $k = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return md5($cipher);
    }
}