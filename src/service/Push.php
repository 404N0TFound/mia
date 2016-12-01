<?php
namespace mia\miagroup\Service;

use mia\miagroup\Lib\Redis;

class Push extends \mia\miagroup\Lib\Service
{
    private $redis;

    public function __construct()
    {
        parent::__construct();
        $this->redis = new Redis('push/default');
    }

    /**
     * 消息推送
     * @param $userId  用户id
     * @param $content  消息内容
     * @param $url  跳转链接
     */
    public function pushMsg($userId, $content, $url)
    {
        $data = [
            "user_id" => intval($userId),
            "content" => $content,
            "url" => $url
        ];
        $res = $this->redis->lpush(\F_Ice::$ins->workApp->config->get('busconf.push.key'),json_encode($data));
        return $this->succ($res);
    }
}