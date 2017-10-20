<?php

namespace mia\miagroup\Daemon\News;

use mia\miagroup\Service\News as NewsService;
use mia\miagroup\Lib\Redis;

class Delaysendmessage extends \FD_Daemon
{
    public function execute()
    {
        $redis = new Redis('news/default');
        $redis_info = \F_Ice::$ins->workApp->config->get('busconf.rediskey.newsKey.delay_to_write_news');
        $key = $redis_info["key"];

        while (true) {
            //消息lpush压入表头的，取从表尾取
            $newsInfo = $redis->rpop($key);

            if (!empty($newsInfo)) {
                $newsService = new NewsService();
                $newsInfo = json_decode($newsInfo, true);

                $ext_info = [];
                $type = $newsInfo["type"];
                $newsType = $newsInfo["newsType"];
                $source_id = 0;

                switch ($type) {
                    case "trade":
                        $source_id = $newsInfo["ext_info"]["orderCode"];
                        unset($newsInfo["ext_info"]["orderCode"]);
                }

                $ext_info = array_merge($ext_info, $newsInfo["ext_info"]);

                $ext_info['time'] = $newsInfo["time"];
                //发送消息
                $newsService->postMessage($newsType, $newsInfo["toUserId"], 0, $source_id, $ext_info);
            } else {
                break;
            }
        }
    }
}