<?php

namespace OAuthTo\Wechat;

use GuzzleHttp\Client;

class Common
{
    private $client = null;
    private $conf = [];

    public function __construct(array $configArr){
        $this->conf = [
            'CORPID' => '',
            'AGENTID' => '',
            'AGENT_SECRET' => '',
            'CONTACTS_SECRET' => '',

            /**
             *
             * 微信支付信息配置
             * TODO: 修改这里配置为您自己申请的商户信息
             * 
             * APPID：绑定支付的APPID（必须配置，开户邮件中可查看）
             * 
             * MCHID：商户号（必须配置，开户邮件中可查看）
             * 
             * KEY：商户支付密钥，参考开户邮件设置（必须配置，登录商户平台自行设置）
             * 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert
             * 
             * APPSECRET：公众帐号secert（仅JSAPI支付的时候需要配置， 登录公众平台，进入开发者中心可设置），
             * 获取地址：https://mp.weixin.qq.com/advanced/advanced?action=dev&t=advanced/dev&token=2005451881&lang=zh_CN
             *
             * NOTIFY_URL: 异步通知url
             * @var string
             */
            'APPID' => '',
            'MCHID' => '',
            'KEY' => '',
            'APP_SECRET' => '',
            'NOTIFY_URL' => '',

            /**
             * TODO：设置商户证书路径
             * 证书路径,注意应该填写绝对路径（仅退款、撤销订单时需要，可登录商户平台下载，
             * API证书下载地址：https://pay.weixin.qq.com/index.php/account/api_cert，下载之前需要安装商户操作证书）
             * @var path
             */
            'SSLCERT_PATH' => '',
            'SSLKEY_PATH' => '',

            //=======【上报信息配置】===================================
            /**
             * TODO：接口调用上报等级，默认紧错误上报（注意：上报超时间为【1s】，上报无论成败【永不抛出异常】，
             * 不会影响接口调用流程），开启上报之后，方便微信监控请求调用的质量，建议至少
             * 开启错误上报。
             * 上报等级，0.关闭上报; 1.仅错误出错上报; 2.全量上报
             * @var int
             */
            'REPORT_LEVENL' => 0,

            //=======【curl代理设置】===================================
            /**
             * TODO：这里设置代理机器，只有需要代理的时候才设置，不需要代理，请设置为0.0.0.0和0
             * 本例程通过curl使用HTTP POST方法，此处可修改代理服务器，
             * 默认CURL_PROXY_HOST=0.0.0.0和CURL_PROXY_PORT=0，此时不开启代理（如有需要才设置）
             * @var unknown_type
             */
            //"10.152.18.220";
            'CURL_PROXY_HOST' => '0.0.0.0',
            //8080;
            'CURL_PROXY_PORT' => 0
        ];

        $keyScope = array_keys($this->conf);
        foreach ($configArr as $configKey => $configValue) {
            if (in_array($configKey, $keyScope, true)) {
                $this->conf[$configKey] = $configValue;
            }
        }

        $this->client = new Client();
    }

    /**
     * Get GuzzleHttp client.
     *
     * @return GuzzleHttp\Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    public function config($key, $default = null)
    {
        if (array_key_exists($key, $this->conf)) {
            return $this->conf[$key];
        }

        return $default;
    }

    public function setConfig($key, $value)
    {
        if ($key) {
            $this->conf[$key] = $value;
        }
    }

    /**
     * Get access token by type.
     * detail: https://work.weixin.qq.com/api/doc#%E7%AC%AC%E4%B8%89%E6%AD%A5%EF%BC%9A%E8%8E%B7%E5%8F%96access_token
     *
     * @param string $type ['contacts' or 'agent']
     *
     * @return array|null
     */
    public function getAccessToken(string $type = 'contacts')
    {
        $secret = $type === 'contacts' ? $this->config('CONTACTS_SECRET') : $this->config('AGENT_SECRET');
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/gettoken'
            . '?corpid=' . $this->config('APPID')
            . '&corpsecret=' . $secret;

        $response = $this->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return [
                'access_token' => $result->access_token,
                'expires_in' => $result->expires_in
            ];
        }

        return null;
    }

    /**
     * get jssdk ticket
     *
     * @author Arno
     *
     * @param  string $agentAccessToken agent access_token please;
     *
     * @return array|null
     */
    public function getTicket(string $agentAccessToken)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=' . $agentAccessToken;

        $response = $this->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return [
                'ticket' => $result->ticket,
                'expires_in' => $result->expires_in
            ];
        }

        return null;
    }

    /**
     * 格式化参数格式化成url参数
     *
     * @author Arno
     *
     * @param  array  $paramArr   参数数组
     * @param  array  $excludeArr 排除键数组
     *
     * @return string
     */
    public function toUrlParams(array $paramArr, array $excludeArr = [])
    {
        $buff = '';
        foreach ($paramArr as $key => $value) {
            if(!in_array($key, $excludeArr, true) && !is_array($value) && !empty($value)) {
                $buff .= $key . '=' . $value . '&';
            }
        }
        
        $buff = trim($buff, '&');
        return $buff;
    }

    /**
     * 生成回调地址
     * detail: https://work.weixin.qq.com/api/doc#10028
     *
     * @author Arno
     *
     * @param  string $redirectUri 授权后重定向的回调链接地址
     * @param  string $state       重定向后会带上state参数，企业可以填写a-zA-Z0-9的参数值，长度不可超过128个字节
     * @param  array  $uriParam       自定义参数
     *
     * @return string              url
     */
    public function generateRedirectUrl(string $redirectUri, string $state, string $scope = 'snsapi_base', array $uriParam = [])
    {
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?response_type=code';

        switch ($scope) {
            case 'snsapi_userinfo':
            case 'snsapi_privateinfo':
                $url .= '&agentid=' . $this->config('AGENTID');
                break;
            default:
                $scope = 'snsapi_base';
        }

        if (!empty($uriParam)) {
            $redirectUri .= '?' . $this->toUrlParams($uriParam);
        }

        return $url . '&scope=' . $scope
            . '&appid=' . $this->config('APPID')
            . '&state=' . $state
            . '&redirect_uri=' . urlencode($redirectUri)
            . '#wechat_redirect';
    }
