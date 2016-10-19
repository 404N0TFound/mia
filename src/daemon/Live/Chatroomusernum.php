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
 class Chatroomusernum extends \FD_Daemon {
     
     public function execute() {
 
         $liveData = new LiveData();
         $liveModel = new LiveModel();
         $rong_api = new RongCloudUtil();
         $redis = new Redis();
         
         //获取正在直播的聊天室的id
         $result = $liveData->getBatchLiveInfo();
         foreach($result as $liveInfo){
             //获取数量
             $audience_num_key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.liveKey.live_audience_online_num.key'),$liveInfo['id']);
             $cache_audience_num = $redis->get($audience_num_key);

             $roomInfo = $liveModel->checkLiveRoomByUserId($liveInfo['user_id']);
             $userNum = 30000;
             $settings  = json_decode($roomInfo['settings'],true);
             if (isset($settings['user_num']) && !empty($settings['user_num']) && $settings['user_num'] > $userNum) {
                 $userNum = $settings['user_num'];
             }
             //变化数量
             $cache_audience_num = $this->increase($cache_audience_num,$userNum);
             //记录数量
             $redis->set($audience_num_key, $cache_audience_num);
             //发送在线人数的消息
             $content = NormalUtil::getMessageBody(5,$liveInfo['chat_room_id'],0,'',['count'=>"$cache_audience_num"]);
             $result = $rong_api->messageChatroomPublish(3782852, $liveInfo['chat_room_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
             if($result['code'] == 200){
                 echo 'success';
             }else{
                 echo 'fail';
             }
         }
     }
     
    private function increase($cache_audience_num,$usersNum) {
         $cache_audience_num = intval($cache_audience_num);
         $usersNum = intval($usersNum);
         $scale = $usersNum/10000 ?: 1; //比例
         //底数为10至50的随机数，每3s一次变化
         if ($cache_audience_num == 0) {
             $cache_audience_num = rand(50, 100)*$scale;
             return intval($cache_audience_num);
         }
         if ($cache_audience_num <= 3000*$scale) {
             //当$cache_audience_num <= 500，70%概率变化，叠加5至20的随机数
             if (rand(0, 100) < 70) {
                 $cache_audience_num += rand(20, 60)*$scale;
             }
             return intval($cache_audience_num);
         } else if ($cache_audience_num > 3000*$scale && $cache_audience_num <= 6000*$scale) {
             //当500 < $cache_audience_num <= 1000，40%概率变化，叠加-5至20的随机数
             if (rand(0, 100) < 40) {
                 $cache_audience_num += rand(-10, 60)*$scale;
             }
             return intval($cache_audience_num);
         } else if ($cache_audience_num > 6000*$scale && $cache_audience_num <= 10000*$scale) {
             //当 1000 < $cache_audience_num < 2000，30%概率变化，叠加-10至20的随机数
             if (rand(0, 100) < 30) {
                 $cache_audience_num += rand(-10, 60)*$scale;
             }
             return intval($cache_audience_num);
         } else {
             //当 $cache_audience_num > 10000，20%概率变化，叠加-20至5的随机数
             if (rand(0, 100) < 20) {
                 $cache_audience_num += rand(-5, 20)*$scale;
             }
             return intval($cache_audience_num);
         }
     }
 }
