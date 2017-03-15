<?php
namespace mia\miagroup\Util;

use mia\miagroup\Lib\RemoteCurl;

class ShumeiUtil
{
    /**
     * ShumeiUtil constructor.
     * 初始配置
     */
    public function __construct($session_info)
    {
        $this->session_info = $session_info;
        $this->_config = \F_Ice::$ins->workApp->config->get('busconf.shumei');
    }

    /**
     * 数美文字合法性认证
     * @param $text
     * @return bool
     */
    public function checkText($text)
    {
        $remote_curl = new RemoteCurl('shumei_text');
        $params['accessKey'] = $this->_config['accessKey'];
        $params['type'] = $this->_config['type'];
        $params['data']['text'] = $text;
        $params['data']['tokenId'] = strval($this->session_info['current_uid']);
        $post_data = json_encode($params);

        $result = $remote_curl->curl_remote('', $post_data);

        $return = true;

        if ($result['code'] == 1100 && $result['riskLevel'] == "REJECT" && json_decode($result['detail'], true)['description'] == "包含过多无意义字符") {
            return $return;
        }
        if ($result['code'] = !1100 || $result['riskLevel'] != "PASS") {
            $reason = json_decode($result['detail'], true);
            $return = $reason['description'] ? $reason['description'] : "请求失败";
        }
        return $return;
    }
}