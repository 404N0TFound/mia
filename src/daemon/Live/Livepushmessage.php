<?php
namespace mia\miagroup\Daemon\Live;

use \mia\miagroup\Data\Live\Live as LiveData;
use mia\miagroup\Data\Live\LiveRoom as LiveRoom;
use mia\miagroup\Service as Service;

/**
 * 检测正在直播的房间，向关注用户推送消息
 * @author user
 *
 */
class Livepushmessage extends \FD_Daemon
{
    public function execute()
    {
        $liveData = new LiveData();
        $fans = new Service\UserRelation();
        $push = new Service\Push();
        $liveRoomData = new LiveRoom();
        $user = new Service\User();

        //获取正在直播的聊天室的id
        $result = $liveData->getBatchLiveIds();

        if (!empty($result)) {
            $ids = array();
            foreach ($result as $val) {
                $ids[] = $val['user_id'];
            }

            $liveRoomData = $liveRoomData->getBatchLiveRoomByUserIds($ids);
            $usersInfo = $user->getUserInfoByUids($ids);
            foreach ($result as $live) {
                //判断是否需要推送消息
                $push_check = $liveRoomData[$live['user_id']]['push_time'];
                if (empty($push_check) || abs(strtotime($push_check) - time()) > 60) {
                    continue;
                }
                //获取该主播所有粉丝
                $userId = $live['user_id'];
                $fansNum = $fans->countBatchUserFanS($userId);

                $limit = 100;
                $end = floor($fansNum['data'][$userId] / $limit) + 1;
                $name = $usersInfo['data'][$live['user_id']]['nickname'];

                $content = [$name . '正在蜜芽直播，快来看→', $name . '喊你来看直播啦，快上车→', '【直播】' . $name . '（主播名称）的直播开始了，别错过→'];//发送内容
                for ($i = 1; $i <= $end; $i++) {
                    $fansList = $fans->getFansList($userId, $i, $limit);
                    foreach ($fansList['data'] as $fans) {
                        //给用户推送消息
                        $push->pushMsg($liveRoomData[$live['user_id']['id']], $content, $fans, 'live');
                    }
                }
            }
        }
    }
}
