<?php 
 namespace mia\miagroup\Daemon\Live;
 
 use mia\miagroup\Util\RongCloudUtil;
 use mia\miagroup\Util\NormalUtil;
 use mia\miagroup\Data\Live\Live as LiveData;
 use mia\miagroup\Lib\Redis;
 
 /**
  * 推送直播在线用户数
  * @author user
  *
  */
 class Chatroomusernum extends \FD_Daemon {
     
     public function execute() {
 
         $liveData = new LiveData();
         $rong_api = new RongCloudUtil();
         $redis = new Redis();
         
         //获取正在直播的聊天室的id
         $result = $liveData->getBatchLiveInfo();
         foreach($result as $liveInfo){
             //获取数量
             $audience_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'),$liveInfo['id']);
             $cache_audience_num = $redis->get($audience_num_key);
             //变化数量
             $cache_audience_num = $this->increase($cache_audience_num);
             //记录数量
             $redis->set($audience_num_key, $cache_audience_num);
             //发送在线人数的消息
             $content = NormalUtil::getMessageBody(5,0,'',['count'=>"$cache_audience_num"]);
             $result = $rong_api->messageChatroomPublish(3782852, $liveInfo['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
             if($result['code'] == 200){
                 echo 'success';
             }else{
                 echo 'fail';
             }
         }
     }
     
     private function increase($cache_audience_num) {
         $cache_audience_num = intval($cache_audience_num);
         //底数为10至50的随机数，每3s一次变化
         if ($cache_audience_num == 0) {
             $cache_audience_num = rand(50, 100);
             return $cache_audience_num;
         }
         if ($cache_audience_num <= 3000) {
             //当$actual_count <= 500，70%概率变化，叠加5至20的随机数
             if (rand(0, 100) < 70) {
                 $cache_audience_num += rand(20, 60);
             }
             return $cache_audience_num;
         } else if ($cache_audience_num > 3000 && $cache_audience_num <= 6000) {
             //当500 < $actual_count <= 1000，40%概率变化，叠加-5至20的随机数
             if (rand(0, 100) < 40) {
                 $cache_audience_num += rand(-10, 60);
             }
             return $cache_audience_num;
         } else if ($cache_audience_num > 6000 && $cache_audience_num <= 10000) {
             //当 1000 < $actual_count < 2000，30%概率变化，叠加-5至20的随机数
             if (rand(0, 100) < 30) {
                 $cache_audience_num += rand(-10, 60);
             }
             return $cache_audience_num;
         } else {
             //当 $actual_count > 10000，20%概率变化，叠加-20至5的随机数
             if (rand(0, 100) < 20) {
                 $cache_audience_num += rand(-5, 20);
             }
             return $cache_audience_num;
         }
     }
 }