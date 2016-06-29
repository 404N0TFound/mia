<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;

class LiveSaleSkuNum extends \FD_Daemon{
	
    public function execute(){
        //规则：底数为0，每5s叠加一个1（含）-50（含）的随机数，最大值72000.
        $actual_count = 0;
        
        $base = 0;
        $max = 72000;
        $random = rand(1, 50);
        $sku_num = $base + $random;
        
        if($sku_num >= $max){
            $actual_count = $max;
        }else{
            $actual_count = $sku_num;
        }

        $roomData = new LiveRoom();
        $rong_api = new RongCloudUtil();
        
        //获取正在直播的聊天室的id
        $result = $roomData->getBatchLiveRoomInfo();
        $content = '{"type":6,"extra":{"count":'.$actual_count.'}}';
        
        foreach($result as $room){
            //发送售卖商品数量的消息
            $rong_api->messageChatroomPublish($fromUserId, $room['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
        }
    }
}