<?php 
namespace mia\miagroup\Daemon\Live;

use mia\miagroup\Service\Redbag as RedBagService;
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
        $redis = new Redis();
        //获取所有正在直播的房间
        $allLiveRooms = $liveRoomData->getAllLiveRoom();
        foreach($allLiveRooms as $room){
            $settings = json_decode($room['settings'],true);
            //判断是否有设置红包
            if(!$settings['redbag'] || intval($settings['redbag']) <= 0){
                continue;
            }
            //判断红包是否已拆散
            // 获取rediskey
            $key = sprintf(\F_Ice::$ins->workApp->config->get('busconf.rediskey.redBagKey.splitRedBag.key'), $settings['redbag']);
            $splitStatus = $redis->exists($key);
            if($splitStatus){
                continue;
            }
            //拆散红包
            $splitStatus = $redBagService->splitRedBag($settings['redbag'])['data'];
            if($splitStatus == false){
                continue;
            }
            // 如果settings里有红包就给主播发消息
            $rong_api = new RongCloudUtil();
            $content = NormalUtil::getMessageBody(11, 0, '', array('redbag_id' => $settings['redbag']));
            $rong_api->messagePublish(3782852, $room['user_id'], \F_Ice::$ins->workApp->config->get('busconf.rongcloud.objectName'), $content);
        }
    }
}