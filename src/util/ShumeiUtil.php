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
        $token_id = !empty($this->session_info['dvc_id']) ? $this->session_info['dvc_id'] : \F_Ice::$ins->runner->request->id;
        $remote_curl = new RemoteCurl('shumei_text');
        $params['accessKey'] = $this->_config['accessKey'];
        $params['type'] = $this->_config['type'];
        $params['data']['text'] = $text;
        $params['data']['tokenId'] = strval($token_id);
        $post_data = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $result = $remote_curl->curl_remote('', $post_data);

        $return = true;

        if ($result['code'] == 1100 && $result['riskLevel'] != "PASS") {
            $reason = json_decode($result['detail'], true);
            $return = $reason['description'] ? $reason['description'] : "内容不合法";
            if(isset($reason['matchedItem']) && !empty($reason['matchedItem'])) {
                $return = "'".$reason['matchedItem']."'命中敏感词";
            }
        }
        return $return;
    }

    public function checkImg($url)
    {
        $token_id = !empty($this->session_info['dvc_id']) ? $this->session_info['dvc_id'] : \F_Ice::$ins->runner->request->id;
        if(empty($token_id)) {
            //pc站只有uid
            $token_id = $this->session_info['current_uid'];
        }
        $remote_curl = new RemoteCurl('shumei_img');
        $params['accessKey'] = $this->_config['accessKey'];
        $params['type'] = $this->_config['imgType'];

        $params['data']['img'] = $url;
        $params['data']['tokenId'] = strval($token_id);
        $post_data = json_encode($params, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $result = $remote_curl->curl_remote('', $post_data);

        $return = true;
        if ($result['code'] == 1100 && $result['riskLevel'] != "PASS") {
            $reason = $result['detail'];
            $return = $reason['description'] ? $reason['description'] : "图片不合法";
        }
        return $return;
    }
}