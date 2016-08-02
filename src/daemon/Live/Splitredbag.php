<?php 
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Service\Redbag as RedBagService;
use mia\miagroup\Model\Redbag as RedBagModel;
use mia\miagroup\Data\Live\LiveRoom as LiveRoomData;
use mia\miagroup\Lib\Redis;
use mia\miagroup\Util\RongCloudUtil;
use mia\miagroup\Util\NormalUtil;

/**
 * 拆散直播红包
 * @author user
 *
 */
class Splitredbag extends \FD_Daemon {
    
    public function execute() {
        $liveRoomData = new LiveRoomData();
        $redBagService = new RedBagService();
        $redBageModel = new RedBagModel();
        $redis = new Redis();
        //获取所有正在直播的房间
        $allLiveRooms = $liveRoomData->getAllLiveRoom();
        foreach($allLiveRooms as $room){
            $settings = json_decode($room['settings'],true);
            //判断是否有设置红包
            if(!$settings['redbag'] || intval($settings['redbag']) <= 0){
                continue;
            }
            $redbagInfo = $redBageModel->getRedbagBaseInfoById($settings['redbag']);
            if (empty($redbagInfo)) {
                continue;
            }
            //判断红包是否已拆散
            //获取rediskey
            $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitStatus.key'), $settings['redbag']);
            $splitStatus = $redis->exists($key);
            if($splitStatus){
                continue;
            }
            // 给主播发展示红包消息
            $rong_api = new RongCloudUtil();
            $content = NormalUtil::getMessageBody(11, $room['chat_room_id'], 0, '', array('redbag_id' => $settings['redbag']));
            $rong_api->messagePublish(3782852, $room['user_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
        }
    }
}