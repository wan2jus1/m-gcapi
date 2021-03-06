<?php
/**
 *  信汇
 * 支付接口调用 的demo
 * User: shenshilin
 * Date: 2017/4/10
 * Time: 15:02
 */
class Xinhui_model extends MY_Model
{

    /**
     * 构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *
     *接口调用 paydata 参数
     *   'bank_o_id'   支付平台的id号
     *   'pay_domain'      异步回调的地址
     *   'pay_return_url'  同步回调的地址
     *   'pay_id'          商户号
     *   'pay_key'         商户密钥
     *   'pay_private_key' 商户私钥
     *   'pay_public_key'  商户公钥
     *   'pay_server_key'  服务端公钥
     *   'pay_server_num'  终端号
     *   'shopurl'         商城域名
     *   'code'            状态值
     *   'bank_type'       网银支付是网银的type
     */
    public function call_interface($order_num, $money, $pay_data)
    {
        $paymoney    =  sprintf('%.2f', $money);
        $version     = "3.0";
        $method      = "Xh.online.pay";
        $partner     = (string)trim($pay_data['pay_id']); //商户ID
        $ordernumber = (string)trim($order_num); //商户订单号
        $tokenKey    = $pay_data['pay_key']; // 密钥
        $callbackurl = $pay_data['pay_domain'].'/index.php/callback/xinhui/callbackurl';
        $hrefbackurl = $pay_data['pay_domain'].'/index.php/callback/xinhui/hrefbackurl';
        $banktype    = $this->return_code($pay_data['code'], $pay_data['bank_type']);
        $isshow      = 1;

        $arrData = [
            'partner'     => $partner,
            'banktype'    => $banktype,
            'paymoney'    => $paymoney,
            'ordernumber' => $ordernumber,
            'callbackurl' => $callbackurl,
            'method'      => $method,
            'version'     => $version,
            'isshow'      => 1,

        ];
        $str  = "version={$version}&method={$method}&partner={$partner}&banktype={$banktype}&paymoney={$paymoney}&ordernumber={$order_num}&callbackurl={$callbackurl}$tokenKey";
        $sign = md5($str);
        $arrData['sign']        = $sign;
        $arrData['hrefbackurl'] = $hrefbackurl;

        //掉掉接口 提交地址提交数据
        $temp['url']    = $pay_data['pay_url'];
        $temp['method'] = 'get';
        $temp['data']   = $arrData;
        ;

        /**跳转第三方**/
        $data['jump'] = 1; //设置支付方式的返回格式
        if ($pay_data['code'] == 2 || $pay_data['code'] == 5) {
            $data['jump'] = 5;
        }
        $url = "{$pay_data['pay_domain']}/index.php/pay/pay_test/pay_sest";//表单提交的地址
        //$url = "http://{$pay_data['pay_domain']}/index.php/callback/Xunjietong/index";//表单提交的地址
        $data['url']    = $url;//提交的地址
        $data['url']    = $url.'/'.$order_num;
        $data['json']  = json_encode($temp, JSON_UNESCAPED_UNICODE);

        return $data;
    }

    /**
     *【支付类型@1微信#2微信app#3微信扫码#
     * 4支付宝#5支付宝APP#6支付宝扫码
     * #7网银#8QQ钱包#9京东钱包#10百度钱包#11点卡
    */
    private function return_code($code, $bank)
    {
        switch ($code) {
            case 1:
                return 'WEIXIN';//微信
            case 2:
                return 'WEIXINWAP';//微信app
            case 4:
                return 'ALIPAY';//支付宝
            case 5:
                return 'ALIPAYWAP';//支付宝app
            case 7:
                return $bank;//网银
            case 8:
                return 'QQ';//qq钱包
            case 9:
                return ''; //京东钱包
            case 10:
                return '';//百度钱包
        }
    }
}
