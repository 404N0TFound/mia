<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Data\Live\Live as LiveData;
use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Lib\Redis;

/**
 * 推送直播在线用户数
 * @author user
 *
 */
class Chatroomusernum extends \FD_Daemon
{
    public function execute()
    {
        $liveData = new LiveData();
        $liveModel = new LiveModel();
        $rong_api = new RongCloudUtil();
        $redis = new Redis();

        //获取正在直播的聊天室的id
        $result = $liveData->getBatchLiveInfo();
        foreach ($result as $liveInfo) {
            //获取数量
            $audience_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'), $liveInfo['id']);
            $cache_audience_num = $redis->get($audience_num_key);

            $roomInfo = $liveModel->checkLiveRoomByUserId($liveInfo['user_id']);
            $userNum = 20000;
            $settings = json_decode($roomInfo['settings'], true);
            if (isset($settings['user_num']) && !empty($settings['user_num']) && $settings['user_num'] > $userNum) {
                $userNum = $settings['user_num'];
            }
            //变化数量
            $cache_audience_num = $this->increase($cache_audience_num, $userNum);
            //记录数量
            $redis->setex($audience_num_key, $cache_audience_num, 3600);echo $cache_audience_num;
            //发送在线人数的消息
            $content = NormalUtil::getMessageBody(5, $liveInfo['chat_room_id'], 0, '', ['count' => "$cache_audience_num"]);
            $result = $rong_api->messageChatroomPublish(3782852, $liveInfo['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
            if ($result['code'] == 200) {
                echo 'success';
            } else {
                echo 'fail';
            }
        }
    }

    private function increase($cache_audience_num, $usersNum)
    {
        $cache_audience_num = intval($cache_audience_num);
        $usersNum = intval($usersNum);

        $avg = round($usersNum / 1200);

        if ($cache_audience_num == 0) {
            $cache_audience_num = rand(25, 200);
            return intval($cache_audience_num);
        }

        //前十分钟,70%概率波动，达到六分之一
        if ($cache_audience_num * 6 <= $usersNum) {
            if (rand(1, 100) <= 70) {
                $cache_audience_num += rand(intval((2 / 7) * $avg), intval((18 / 7) * $avg));
            }
            return intval($cache_audience_num);
        }

        //到了六分之一，大波动下，增加十八分之一，约36s
        if ($cache_audience_num * 6 > $usersNum && ($cache_audience_num * 18 - $usersNum * 3) < $usersNum) {
            if (rand(1, 100) <= 50) {
                $cache_audience_num += rand(intval((1 / 144) * $usersNum), intval((1 / 72) * $usersNum));
            }
            return intval($cache_audience_num);
        }


        //十分钟到半小时
        if ($cache_audience_num * 6 > $usersNum && $cache_audience_num * 2 <= $usersNum) {
            if (rand(1, 100) <= 80) {
                $cache_audience_num += rand(intval((1 / 2) * $avg), intval(2 * $avg));
            }
            return intval($cache_audience_num);
        }

        //到了半小时，大波动下，增加十八分之一，约36s
        if ($cache_audience_num * 2 > $usersNum && ($cache_audience_num * 18 - $usersNum * 9) < $usersNum) {
            if (rand(1, 100) <= 50) {
                $cache_audience_num += rand(intval((1 / 144) * $usersNum), intval((1 / 72) * $usersNum));
            }
            return intval($cache_audience_num);
        }

        //半小时到最大值
        if ($cache_audience_num * 2 > $usersNum && $cache_audience_num < $usersNum) {
            if (rand(1, 100) <= 50) {
                $cache_audience_num += rand(intval((6 / 5) * $avg), intval((14 / 5) * $avg));
            }
            return intval($cache_audience_num);
        }

        //超过最大值，半小时涨1w（3w基础）
        if ($cache_audience_num >= $usersNum) {
            $range = intval($usersNum / 3);
            $step = ($range / 600);
            if (rand(1, 100) <= 40) {
                $cache_audience_num += rand(2 * $step, 3 * $step);
            }
            return intval($cache_audience_num);
        }
    }
}
