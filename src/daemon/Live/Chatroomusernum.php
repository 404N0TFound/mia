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
             $redis->setex($audience_num_key, $cache_audience_num, 3600);
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

     private function increase($cache_audience_num, $usersNum)
     {
         $cache_audience_num = intval($cache_audience_num);
         $usersNum = intval($usersNum);

         $rate = $usersNum / $cache_audience_num;
         //底数为10至50的随机数，每3s一次变化
         if ($cache_audience_num == 0) {
             $cache_audience_num = rand(25, 200);
             return intval($cache_audience_num);
         }
         if ($rate >= 6) {
             //前6分之1，10分钟左右达到，200次
             $increase = round($usersNum / (6 * 140));
             if (rand(0, 100) < 70) {
                 $cache_audience_num += rand($increase - 15, $increase + 15);
             }
             return intval($cache_audience_num);
         } else if ($rate < 6 && $rate >= 2) {
             //6分之一到1半,30分钟达到
             $increase = round($usersNum / (2 * 200));
             if (rand(0, 100) < 50) {
                 $cache_audience_num += rand($increase - 20, $increase + 20);
             }
             return intval($cache_audience_num);
         } else if ($rate >= 1 && $rate < 2) {
             //一半到最大值
             $increase = round($usersNum / (2 * 200));
             if (rand(0, 100) < 50) {
                 $cache_audience_num += rand($increase - 10, $increase + 10);
             }
             return intval($cache_audience_num);
         } else {
             //超过最大值
             if (rand(0, 100) < 20) {
                 $cache_audience_num += rand(-5, 10);
             }
             return intval($cache_audience_num);
         }
     }
 }
