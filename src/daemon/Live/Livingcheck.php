<?php
namespace mia\miagroup\Daemon\Live;
use mia\miagroup\Service\Live as LiveService;
use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Util\QiniuUtil;

/**
 * 直播状态更新
 * 直播开始30秒后，推流已断开的，设置为直播已停止
 */
class LivingCheck extends \FD_Daemon {
    
    private $liveModel;
    private $liveService;
    private $qiniuUtil;
    
    public function __construct() {
        $this->liveModel = new LiveModel();
        $this->liveService = new LiveService();
        $this->qiniuUtil = new QiniuUtil();
    }
    
    public function execute() {
        //获取状态为3(直播中)的直播
        $where['status'] = array(':eq', 'status', 3);
        $where['create_time'] = array(':gt', 'create_time', time() - 86400 * 30);
        $lives = $this->liveModel->getLiveList($where, 0, 1000);
        if (!empty($lives)) {
            //获取用户的房间号
            $userIds = array();
            foreach ($lives as $live) {
                $userIds[] = $live['user_id'];
            }
            $roomInfos = $this->liveModel->checkLiveRoomByUserIds($userIds);
            //检查已经直播30秒，已经断流的直播
            foreach ($lives as $live) {
                if(strtotime($live['start_time']) + 30 < time()){
                    $status = $this->qiniuUtil->getStatus($live['stream_id']);
                    if($status == 'disconnected'){
                        $this->liveService->endLive($live['user_id'], $roomInfos[$live['user_id']]['id'], $live['id'], $live['chat_room_id']);
                    }
                }
            }
        }
    }
}
