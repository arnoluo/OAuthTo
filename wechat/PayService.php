<?php

namespace OAuthTo\Wechat;

use OAuthTo\Wechat\Common;

class PayService
{
    private $common = null;
    private $test = false;
    private $sandboxKey = '';

    public function __construct(array $configArr, bool $isTest = false)
    {
        $this->common = new Common($configArr);
        $this->test = $isTest;
        if ($isTest) {
            if ($sandboxKey = $this->getSandboxKey()) {
                $this->common->setConfig('KEY', $sandboxKey)
            }
        }
    }

    /**
     * 
     * 统一下单
     * out_trade_no、body、total_fee、trade_type, spbill_create_ip必填
     * appid、mchid、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @return 成功时返回创建结果，其他抛异常
     */
    public function unifiedOrder($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $url = $this->sandboxUrl($url);
        
        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'notify_url' => $this->notifyUrl,
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];

        //签名
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 查询订单，out_trade_no、transaction_id至少填一个
     * appid、mchid、nonce_str、spbill_create_ip不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function orderQuery($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";
        $url = $this->sandboxUrl($url);

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 关闭订单，out_trade_no必填
     * appid、mchid、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function closeOrder($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/closeorder";
        $url = $this->sandboxUrl($url);

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }

    /**
     * 
     * 申请退款，out_trade_no、transaction_id至少填一个且
     * out_refund_no、total_fee、refund_fee、op_user_id为必填参数
     * appid、mchid、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function refund($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $url = $this->sandboxUrl($url);

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);

        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, true, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 查询退款
     * 提交退款申请后，通过调用该接口查询退款状态。退款有一定延时，
     * 用零钱支付的退款20分钟内到账，银行卡支付的退款3个工作日后重新查询退款状态。
     * out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个
     * appid、mchid、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function refundQuery($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/refundquery";
        $url = $this->sandboxUrl($url);

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);

        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, true, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 下载对账单，bill_date为必填参数
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function downloadBill($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/pay/downloadbill";
        $url = $this->sandboxUrl($url);

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        if(substr($response, 0 , 5) == "<xml>"){
            return "";
        }
        return $response;
    }
    
    /**
     * @todo 暂未使用
     * 
     * 提交被扫支付API
     * 收银员使用扫码设备读取微信用户刷卡授权码以后，二维码或条码信息传送至商户收银台，
     * 由商户收银台或者商户后台调用该接口发起支付。
     * WxPayWxPayMicroPay中body、out_trade_no、total_fee、auth_code, spbill_create_ip参数必填
     * appid、mchid、、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     */
    public function micropay($param, $timeOut = 10)
    {
        $url = "https://api.mch.weixin.qq.com/pay/micropay";
        $url = $this->sandboxUrl($url);
        
        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        $result = WxPayResults::Init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 撤销订单API接口，参数out_trade_no和transaction_id必须填写一个
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     */
    public function reverse($param, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/secapi/pay/reverse";
        $url = $this->sandboxUrl($url);
        
        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, true, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 测速上报，该方法内部封装在report中，使用时请注意异常流程
     * WxPayReport中interface_url、return_code、result_code、user_ip、execute_time_必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param array $param
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function report($param, $timeOut = 1)
    {
        $url = "https://api.mch.weixin.qq.com/payitil/report";
        $url = $this->sandboxUrl($url);

        $inputObj->SetUser_ip($_SERVER['REMOTE_ADDR']);//终端ip
        $inputObj->SetTime(date("YmdHis"));//商户上报时间  
        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        return $response;
    }
    
    /**
     * 
     * 生成二维码规则,模式一生成支付二维码,product_id必填
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param integer $productId
     * @param int $timeOut
     * @throws WxPayException
     * @return 成功时返回，其他抛异常
     */
    public function bizpayurl($productId, $timeOut = 6)
    {   
        $param = [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'product_id' => $productId,
            'time_stamp' => time(),
            'nonce_str' => $this->getNonceStr()//随机字符串
        ];
        
        $param['sign'] = $this->makeSign($param);

        $url = "weixin://wxpay/bizpayurl?" . $this->common->toUrlParams($param, ['sign']);
        
        return $this->shortUrl($url);
    }
    
    /**
     * 
     * 转换短链接
     * longUrl 必填
     * 该接口主要用于扫码原生支付模式一中的二维码链接转成短链接(weixin://wxpay/s/XXXXXX)，
     * 减小二维码数据量，提升扫描速度和精确度。
     * appid、mchid、spbill_create_ip、nonce_str不需要填入
     * @param string $longUrl
     * @param int $timeOut
     * 
     * @return 成功时返回，其他抛异常
     */
    public function shortUrl($longUrl, $timeOut = 6)
    {
        $url = "https://api.mch.weixin.qq.com/tools/shorturl";

        $param += [
            'appid' => $this->appId,//公众账号ID
            'mch_id' => $this->mchId,//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
            'long_url' => $longUrl
        ];
        
        //makeSign需使用原long_url
        $param['sign'] = $this->makeSign($param);
        //传输需urlencode
        $param['long_url'] = urlencode($longUrl);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false, $timeOut);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        return $result;
    }
    
    /**
     * 
     * 支付结果通用通知
     * @param function $callback
     * 
     */
    public function notify(&$msg, $notifyType = 'native', $needSign = true, $order = [])
    {      
        //根据通知类型执行处理函数
        $result = false;
        switch ($notifyType) {
            case 'native' :
                $result = $this->nativeNotifyProcess($msg, $order);
                break;
            case 'payment' :
                $result = $this->paymentNotifyProcess($msg, $order);
                break;
        }
        
        $reply = array();
        if($result == false){
            $reply['return_code'] = "FAIL";
            $reply['return_msg'] = $msg;
        } else {
            //该分支在成功回调后，打包信息返回给微信系统。
            if (is_array($result)) {
                $reply = $result;
            }
            $reply['return_code'] = "SUCCESS";
            $reply['return_msg'] = "OK";
            if ($needSign) {
                $reply['sign'] = $this->makeSign($reply);
            }
        }
        
        return $reply;
    }

    public function nativeNotifyProcess(&$msg, $order)
    {
        if ($msg !== 'OK') {
            return false;
        }

        $result = $this->unifiedOrder($order);
        if(!array_key_exists("appid", $result) ||
            !array_key_exists("mch_id", $result) ||
            !array_key_exists("prepay_id", $result)) {
            $msg = "统一下单失败";
            return false;
        }
        
        return [
            "appid" => $result["appid"],
            "mch_id" => $result["mch_id"],
            "nonce_str" => $this->getNonceStr(),
            "prepay_id" => $result["prepay_id"],
            "result_code" => "SUCCESS",
            "err_code_des" => "OK"
        ];
    }

    public function paymentNotify(&$msg, $order = [])
    {
        if ($msg == 'OK') {
            $result = $this->orderQuery($order);
            if(array_key_exists("return_code", $result)
                && array_key_exists("result_code", $result)
                && $result["return_code"] == "SUCCESS"
                && $result["result_code"] == "SUCCESS") {

                return [
                    'return_code' => 'SUCCESS',
                    'return_msg' => $msg,
                    'trade_state' => $result['trade_state']
                ];
            }

            $msg = '参数格式校验错误';
        }

        return [
            'return_code' => 'FAIL',
            'return_msg' => $msg,
        ];
    }
    
    /**
     * 
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public function getNonceStr($length = 32) 
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {  
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
        } 
        return $str;
    }
    
    /**
     * 
     * 上报数据， 上报的时候将屏蔽所有异常流程
     * @param string $usrl
     * @param int $startTimeStamp
     * @param array $data
     */
    private function reportCostTime($url, $startTimeStamp, $data)
    {
        //如果不需要上报数据
        if(config('weChat.REPORT_LEVENL') == 0){
            return;
        } 
        //如果仅失败上报
        if(config('weChat.REPORT_LEVENL') == 1 &&
             array_key_exists("return_code", $data) &&
             $data["return_code"] == "SUCCESS" &&
             array_key_exists("result_code", $data) &&
             $data["result_code"] == "SUCCESS")
         {
            return;
         }
         
        //上报逻辑
        $endTimeStamp = $this->getMillisecond();
        $objInput = new WxPayReport();
        $objInput->SetInterface_url($url);
        $objInput->SetExecute_time_($endTimeStamp - $startTimeStamp);
        //返回状态码
        if(array_key_exists("return_code", $data)){
            $objInput->SetReturn_code($data["return_code"]);
        }
        //返回信息
        if(array_key_exists("return_msg", $data)){
            $objInput->SetReturn_msg($data["return_msg"]);
        }
        //业务结果
        if(array_key_exists("result_code", $data)){
            $objInput->SetResult_code($data["result_code"]);
        }
        //错误代码
        if(array_key_exists("err_code", $data)){
            $objInput->SetErr_code($data["err_code"]);
        }
        //错误代码描述
        if(array_key_exists("err_code_des", $data)){
            $objInput->SetErr_code_des($data["err_code_des"]);
        }
        //商户订单号
        if(array_key_exists("out_trade_no", $data)){
            $objInput->SetOut_trade_no($data["out_trade_no"]);
        }
        //设备号
        if(array_key_exists("device_info", $data)){
            $objInput->SetDevice_info($data["device_info"]);
        }
        
        $this->report($objInput);
    }

    /**
     * 以post方式提交xml到对应的接口url
     * 
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws WxPayException
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {       
        //设置超时
        $option = array();
        $option['timeout'] = $second;
        
        //如果有配置代理这里就设置代理
        if(config('weChat.CURL_PROXY_HOST') !== "0.0.0.0" 
            && config('weChat.CURL_PROXY_PORT') !== 0){
            $option['proxy'] = 'tcp://' . config('weChat.CURL_PROXY_HOST');
            $option['proxy'] .= ':' . config('weChat.CURL_PROXY_PORT');
        }

        //$option['verify'] = true;
    
        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            $option['cert'] = [
                config('weChat.SSLCERT_PATH') => config('weChat.SSLKEY_PATH')
            ];
        }
        
        $option['body'] = $xml;

        $response = $this->common->getClient()->request('POST', $url, $option)->getBody()->getContents();
        
        //返回结果
        return $response;
    }
    
    /**
     * 获取毫秒级别的时间戳
     */
    private function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime());
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }

    /**
     * 生成签名
     * @param   array $order 统一订单参数数组
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($order)
    {
        //签名步骤一：按字典序排序参数
        ksort($order);
        $string = $this->common->toUrlParams($order, ['sign']);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->apiKey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 输出xml字符
    **/
    public function toXml($orderArray)
    {   
        $xml = "<xml>";
        foreach ($orderArray as $key => $val) {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            } else {
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml; 
    }

    /**
     * 将xml转为array
     * @param string $xml
     */
    public function fromXml($xml)
    {   
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);        
    }

    /**
     * 将xml转为array
     * @param string $xml
     */
    public function init($xml)
    {   
        $response = $this->fromXml($xml);

        //fix bug 2015-06-29
        if($response['return_code'] != 'SUCCESS'){
             return $response;
        }
        if ($this->checkSign($response)) {
            return $response;
        }
        return false;
    }

    /**
     * 
     * 检测签名
     */
    public function checkSign($response)
    {
        //fix异常
        if(!array_key_exists('sign', $response)){
            return "签名错误！";
        }
        
        $sign = $this->makeSign($response);
        if($response['sign'] == $sign){
            return true;
        }
        return "签名错误！";
    }

    /**
     * 
     * 构造获取code的url连接
     * @param string $redirectUrl 微信服务器回跳的url，需要url编码
     * 
     * @return 返回构造好的url
     */
    private function requestOauthCode($redirectUrl)
    {
        $urlObj["appid"] = $this->appId;
        $urlObj["redirect_uri"] = "$redirectUrl";
        $urlObj["response_type"] = "code";
        $urlObj["scope"] = "snsapi_base";
        $urlObj["state"] = "STATE"."#wechat_redirect";
        $bizString = $this->common->toUrlParams($urlObj, ['sign']);
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
        $this->common->getClient()->request('POST', $url);
    }
    
    /**
     * 
     * 构造获取open和access_toke的url地址
     * @param string $code，微信跳转带回的code
     * 
     * @return 请求的url
     */
    private function getOpenidByCode($code)
    {
        $urlObj["appid"] = $this->appId;
        $urlObj["secret"] = $this->appSecret;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $bizString = $this->common->toUrlParams($urlObj, ['sign']);
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
        $response = $this->common->getClient()->request('POST', $url)->getBody();
        if ($data = json_decode($response)) {
            return $data['openid'];
        }

        return false;
    }

    /**
     * Get access token by type.
     *
     * @param string $type
     *
     * @return string|null
     */
    private function getAccessToken($type = 'contacts')
    {
        $secret = $type === 'contacts' ? config('weChat.APP_CONTACTS_SECRET') : config('weChat.APP_AGENT_SECRET');
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken'
            . '?corpid=' . $this->appId
            . '&corpsecret=' . $secret;

        $result = $this->common->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($result);
        if ($result && $result->errcode === 0) {
            Cache::put($cacheName, $result->access_token, $result->expires_in / 60);
            return $result->access_token;
        }

        return null;
    }

    public function getOpenIdByUserId($userId)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_openid?access_token=" . $this->getAccessToken();

        $parameters = ['userid' => $userId];

        $result = $this->common->getClient()
            ->request('POST', $url, ['body' => json_encode($parameters, JSON_UNESCAPED_UNICODE)])
            ->getBody();
        $result = json_decode($result);
        if (!$result->errcode) {
            return $result->openid;
        }

        return false;
    }

    public function getUserIdByOpenId($openId)
    {
        $url = "https://qyapi.weixin.qq.com/cgi-bin/user/convert_to_userid?access_token=" . $this->getAccessToken();

        $parameters = ['openid' => $openId];

        $result = $this->common->getClient()
            ->request('POST', $url, ['body' => json_encode($parameters, JSON_UNESCAPED_UNICODE)])
            ->getBody();
        $result = json_decode($result);
        if (!$result->errcode) {
            return $result->userid;
        }

        return false;
    }

    public function getJsapiTicket()
    {
        if (Cache::has('ticket')) {
            return Cache::get('ticket');
        }

        $url = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=' . $this->getAccessToken();

        $result = $this->common->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($result);

        Cache::put('ticket', $result->ticket, $result->expires_in / 60);
        return $result->ticket;
    }

    public function sandboxUrl($url)
    {
        if ($this->isTest) {
            $url = substr_replace($url, '/sandboxnew', 29, 0);
        }

        return $url;
    }

    /**
     * Make pay info for jsapi payment
     *
     * @author Arno
     *
     * @param  array     $param   [description]
     *
     * @return $param              [description]
     */
    public function makeJsapiPayInfo($prepayId, $url)
    {
        $timeStamp = time();
        $nonceStr = $this->getNonceStr();
        $param = [
            'appId' => $this->appId,//公众账号ID
            'timeStamp' => $timeStamp,
            'nonceStr' => $nonceStr,//随机字符串
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'MD5'
        ];

        //支付签名
        $param['paySign'] = $this->makeSign($param);

        //jsapi签名
        $ticket = $this->getJsapiTicket();
        $str = "jsapi_ticket=$ticket&noncestr=$nonceStr&timestamp=$timeStamp&url=$url";
        $param += [
            'ticket' => $ticket,
            'url' => $url,
            'str' => $str,
        ];
        $param['signature'] = sha1($str);
        
        return $param;
    }

    private function sandboxCache(string $signKey = '')
    {
        if (!empty($signKey)) {
            $this->sandboxKey = $signKey;
            return;
        }

        if (!empty($this->sandboxKey)) {
            return $this->sandboxKey;
        }

        return null;
    }

    public function getSandboxKey()
    {
        $sandboxString = 'sandbox_signkey';
        if ($signKey = $this->sandboxCache()) {
            return $signKey;
        }

        $url = "https://api.mch.weixin.qq.com/sandboxnew/pay/getsignkey";
        
        $param = [
            'mch_id' => $this->common->config('MCHId'),//商户号
            'nonce_str' => $this->getNonceStr(),//随机字符串
        ];

        $param['sign'] = $this->makeSign($param);
        $xml = $this->toXml($param);
        
        $startTimeStamp = $this->getMillisecond();//请求开始时间
        $response = $this->postXmlCurl($xml, $url, false);
        $result = $this->init($response);
        $this->reportCostTime($url, $startTimeStamp, $result);//上报请求花费时间
        
        if ($result['return_code'] == 'SUCCESS') {
            $this->sandboxCache($result['sandbox_signkey']);
            return $result['sandbox_signkey'];
        }

        return null;
    }
}
