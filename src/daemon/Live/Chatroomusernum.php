<?php 
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Data\Live\LiveRoom;
use mia\miagroup\Lib\Redis;

class Chatroomusernum extends \FD_Daemon {
    
    public function execute() {

        $roomData = new LiveRoom();
        $rong_api = new RongCloudUtil();
        $redis = new Redis();
        
        //获取正在直播的聊天室的id
        $result = $roomData->getBatchLiveRoomInfo();
        foreach($result as $room){
            //获取数量
            $audience_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'),$room['live_id']);
            $cache_audience_num = $redis->get($audience_num_key);
            
            //规则:底数为1（含）-50（含）的随机数，每5s，叠加一个0（含）-20（含）的随机数，最大值14400
            $actual_count = 0;//初始化
            $max = 14400;
            $random = rand(0, 20);
            if(empty($cache_audience_num)){
                $base = rand(1, 50);
                $user_num = $base + $random;
            }else{
                $user_num = $cache_audience_num + $random;
            }
            if($user_num >= $max){
                $actual_count = $max;
            }else{
                $actual_count = $user_num;
            }
            //记录数量
            $redis->set($audience_num_key,$actual_count);
            //发送在线人数的消息
            $content = '{"type":5,"extra":{"count":"'.$actual_count.'"}}';
            $result = $rong_api->messageChatroomPublish($fromUserId, $room['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
        }
        
//         $this->output(array('code' => 0));
    }
    
}