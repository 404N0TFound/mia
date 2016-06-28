<?php 
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Data\Live\LiveRoom;
use RongCloud\Api;

class ChatroomUserNum extends \FD_Daemon {
    
    public function execute() {
       //底数为1（含）-50（含）的随机数，每5s，叠加一个0（含）-20（含）的随机数，最大值14400
        $actual_count = 0;
        
        $base = rand(1, 50);
        $max = 14400;
        $random = rand(0, 20);
        $user_num = $base + $random;
        
        if($user_num >= $max){
            $actual_count = $max;
        }else{
            $actual_count = $user_num;
        }
        
        
        $roomData = new LiveRoom();
        $rong_api = new RongCloudUtil();
        
        //获取正在直播的聊天室的id
        $result = $roomData->getBatchLiveRoomInfo();
        //消息结构体
        $content = '{"type":5,"extra":{"count":"'.$actual_count.'"}}';
        foreach($result as $room){
            //发送在线人数的消息
            $result = $rong_api->messageChatroomPublish($fromUserId, $room['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
        }
        
        $this->output(array('code' => 0));
    }
    
}