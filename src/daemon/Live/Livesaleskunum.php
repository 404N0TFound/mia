<?php
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Util\NormalUtil;
use mia\miagroup\Data\Live\LiveRoom;
use mia\miagroup\Lib\Redis;

class Livesaleskunum extends \FD_Daemon{
	
    public function execute(){

        $redis = new Redis();
        $roomData = new LiveRoom();
        $rong_api = new RongCloudUtil();
        
        //获取正在直播的聊天室的id
        $result = $roomData->getBatchLiveRoomInfo();
        foreach($result as $room){
            //获取数量
            $sale_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_sale_num.key'),$room['live_id']);
            $cache_sale_num = $redis->get($sale_num_key);
            
            //规则：底数为0，每5s叠加一个1（含）-50（含）的随机数，最大值72000.
            $actual_count = 0;//初始化
            $max = 72000;//最大数
            $random = rand(1, 50);
            
            if(empty($cache_sale_num)){
                //第一次获取数量
                $base = 0;//底数
                $sku_sale_num = $base + $random;
            }else{
                $sku_sale_num = $cache_sale_num + $random;
            }
            if($sku_sale_num >= $max){
                $actual_count = $max;
            }else{
                $actual_count = $sku_sale_num;
            }
            //记录数量
            $redis->set($sale_num_key,$actual_count);
            //发送售卖商品数量的消息
//             $content = NormalUtil::getMessageBody(6,0,'',['count'=>"$actual_count"]);
            $content = NormalUtil::getMessageBody(2,3782852,'this is skusalenum');
            $result = $rong_api->messageChatroomPublish(3782852, $room['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
            if($result['code'] == 200){
                echo 'success';
            }else{
                echo 'fail';
            }
        }
        
    }
    
}