<?php

namespace OAuthTo\Wechat;

use OAuthTo\Wechat\Common;

class WorkService
{
    private $common = null;

    public function __construct($corpId, $agentId, $agentSecret, $contactSecret)
    {
        $this->common = new Common([
            'CORPID' => $corpId, 
            'AGENTID' => $agentId,
            'AGENT_SECRET' => $agentSecret,
            'CONTACTS_SECRET' => $contactSecret
        ]);
    }

    /**
     * 通过code获取用户信息
     * detail: https://work.weixin.qq.com/api/doc#10719
     *
     * @author Arno
     *
     * @param  string  $code 回调页面携带的code
     *
     * @return mixed|null
     */
    public function getUserInfoByCode($code)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token='
            . $this->common->getAccessToken('agent')
            . '&code=' . $code;

        $response = $this->common->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($response);
        if (!empty($result->UserId)) {
            return $result;
        }

        return null;
    }

    public function getTicket($agentAccessToken)
    {
        return $this->common->getTicket($agentAccessToken);
    }

    /**
     * Create a department. See the detail at https://work.weixin.qq.com/api/doc#10076
     *
     * @param array             $parameters
     *        参数格式:[
     *            name
     *            parentid
     *            order(optional)
     *            id(optional)
     *        ]
     *
     * @return integer|null The id of the department.
     */
    public function createDepartment($parameters)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/department/create?access_token='
            . $this->common->getAccessToken('contacts');

        $response = $this->common->getClient()
            ->request('POST', $url, ['body' => json_encode($parameters, JSON_UNESCAPED_UNICODE)])
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return $result->id;
        }

        return null;
    }


    /**
     * Update a department. See the detail at https://work.weixin.qq.com/api/doc#10077
     *
     * @param array             $parameters
     *        参数格式:[
     *            id
     *            name(optional)
     *            parentid(optional)
     *            order(optional)
     *        ]
     *
     * @return bool
     */
    public function updateDepartment($parameters)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/department/update?access_token='
            . $this->common->getAccessToken('contacts');

        $response = $this->common->getClient()
            ->request('POST', $url, ['body' => json_encode($parameters, JSON_UNESCAPED_UNICODE)])
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * Delete a department. See the detail at https://work.weixin.qq.com/api/doc#10079
     *
     * @param  integer $id
     *
     * @return bool
     */
    public function deleteDepartment($id)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/department/delete?access_token='
            . $this->common->getAccessToken('contacts')
            . '&id=' . $id;

        $response = $this->common->getClient()
            ->request('GET', $url)
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * Update a user. See the detail at https://work.weixin.qq.com/api/doc#10077
     *
     * @param array             $parameters
     *        参数格式: [
     *            userid
     *            department(optional)
     *        ]
     *
     * @return bool
     */
    public function updateUser($parameters)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/update?access_token='
            . $this->common->getAccessToken('contacts');

        $response = $this->common->getClient()
            ->request('POST', $url, ['body' => json_encode($parameters, JSON_UNESCAPED_UNICODE)])
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * Delete a user. See the detail at https://work.weixin.qq.com/api/doc#10030
     *
     * @param integer $id
     *
     * @return bool
     */
    public function deleteUser($id)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/delete?access_token='
            . $this->common->getAccessToken('contacts')
            . '&userid=' . $id;

        $response = $this->common->getClient()
            ->request('GET', $url)
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * Send textCard. detail: https://work.weixin.qq.com/api/doc#10167
     *
     * @author Arno
     *
     * @param  array  $textCard    消息体，一维数组
     *         格式:[
     *             url         网页消息地址
     *             title       标题，不超过128个字节，超过会自动截断
     *             description 描述，不超过512个字节，超过会自动截断
     *             btntxt      按钮文字。 默认为“详情”， 不超过4个文字，超过自动截断。
     *         ]
     * @param  string $state       state可填a-zA-Z0-9的参数值（不超过128个字节），用于第三方自行校验session，防止跨域攻击。
     * @param  array  $to          enum:['touser' => ['id1', 'id2'], 'toparty', 'totag'], 为空则为全员群发
     *
     * @return bool   true/false
     */
    public function textCard(array $textCard, string $state, array $to = [])
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='
            . $this->common->getAccessToken('agent');

        $response = $this->common->getClient()
            ->request('POST', $url, ['body' => $this->generateMsgBody('textcard', $textCard, $to)])
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * 图文消息, detail: https://work.weixin.qq.com/api/doc#10167/%E5%9B%BE%E6%96%87%E6%B6%88%E6%81%AF
     *
     * @author Arno
     *
     * @param  array  $articles    消息体，二维数组，包含多个article信息
     *         单个article格式:[
     *             url         网页消息地址
     *             title       标题，不超过128个字节，超过会自动截断
     *             description 描述，不超过512个字节，超过会自动截断
     *             picurl      图文消息的图片链接，支持JPG、PNG格式，较好的效果为大图640x320，小图80x80。
     *             btntxt      按钮文字，仅在图文数为1条时才生效。 默认为“阅读全文”， 不超过4个文字，超过自动截断。该设置只在企业微信上生效，微工作台（原企业号）上不生效。
     *         ]
     * @param  string $state       state可填a-zA-Z0-9的参数值（不超过128个字节），用于第三方自行校验session，防止跨域攻击。
     * @param  array  $to          enum:['touser' => ['id1', 'id2'], 'toparty', 'totag'], 为空则为全员群发
     *
     * @return bool                true/false
     */
    public function news(array $articles, string $state, array $to = [])
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token='
            . $this->common->getAccessToken('agent');

        $response = $this->common->getClient()
            ->request('POST', $url, ['body' => $this->generateMsgBody('news', $articles, $to)])
            ->getBody()
            ->getContents();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return true;
        }

        return false;
    }

    /**
     * 读取成员
     * detail: https://work.weixin.qq.com/api/doc#10019
     *
     * @author Arno
     *
     * @param  string  $userId  成员UserID。对应管理端的帐号，企业内必须唯一。不区分大小写，长度为1~64个字节
     *
     * @return mixed|null
     */
    public function getUserInfoById(string $userId)
    {
        $url = 'https://qyapi.weixin.qq.com/cgi-bin/user/get?access_token='
            . $this->common->getAccessToken('agent')
            . '&userid=' . $userId;

        $response = $this->common->getClient()
            ->request('GET', $url)
            ->getBody();

        $result = json_decode($response);
        if ($result && $result->errcode === 0) {
            return $result;
        }

        return null;
    }

    /**
     * 生成消息体
     * detail: https://work.weixin.qq.com/api/doc#10167
     *
     * @author Arno
     *
     * @param  string  $msgType 消息类型
     * @param  array   $info    消息体内容
     * @param  array   $to      发送群体
     * @param  integer $safe    表示是否是保密消息，0表示否，1表示是，默认0
     *
     * @return string|Exception
     */
    public function generateMsgBody(string $msgType, array $info, array $to, $safe = 0)
    {
        $this->msgBodyFilter($msgType, $info);

        $body = [
            'msgtype' => $msgType,
            'agentid' => $this->common->config('AGENTID'),
            $msgType => [],
            'safe' => $safe
        ];

        switch ($msgType) {
            case 'news':
            case 'mpnews':
                $body[$msgType]['articles'] = $info;
                break;
            default:
                $body[$msgType] = $info;
        }

        if (empty($to)) {
            $to = ['touser' => '@all'];
        } else {
            foreach ($to as $groupType => $idArray) {
                if (!in_array($groupType, ['touser', 'toparty', 'totag'], true)) {
                    unset($to[$groupType]);
                    continue;
                }

                $to[$groupType] = implode('|', $idArray);
            }
        }

        return json_encode(array_merge($body, $to), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 消息体校验过滤
     * detail: https://work.weixin.qq.com/api/doc#10167
     *
     * @author Arno
     *
     * @param  string  $msgType 消息类型
     * @param  array   $info    消息体内容
     *
     * @return bool/Exception
     */
    private function msgBodyFilter($msgType, $info)
    {
        $msgBodyKey = [
            'text' => ['content'],
            'voice' => ['media_id'],
            'image' => ['media_id'],
            'video' => ['media_id'], // 'title', 'description' 为可选项
            'file' => ['media_id'],
            'textcard' => ['title', 'description', 'url'],// 'btntxt' 为可选项
            'news' => ['title', 'url'], // 'description', 'picurl', 'btntxt' 为可选项
            'mpnews' => ['title', 'thumb_media_id', 'content'] // 'author', 'content_source_url', 'digest' 为可选项
        ];

        if (in_array($msgType, ['news', 'mpnews'], true)) {
            foreach ($info as $article) {
                if ($lack = array_diff($msgBodyKey[$msgType], array_keys($article))) {
                    throw new \Exception('Required parameters not found: ' . json_encode($lack));
                }
            }
        } else {
            if ($lack = array_diff($msgBodyKey[$msgType], array_keys($info))) {
                throw new \Exception('Required parameters not found: ' . json_encode($lack));
            }
        }

        return true;
    }
}
