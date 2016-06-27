<?php
namespace mia\miagroup\Daemon\Live;
use mia\miagroup\Service\Live as LiveService;
use mia\miagroup\Model\Live as LiveModel;
use mia\miagroup\Util\QiniuUtil;
/**
 * 直播流状态检查
 * 直播开始，音频、视频信号都ok后，设置直播状态为直播中
 */
class Streamprepare extends \FD_Daemon {

    private $liveModel;
    private $liveService;
    private $qiniuUtil;

    public function __construct() {
        $this->liveModel = new LiveModel();
        $this->liveService = new LiveService();
        $this->qiniuUtil = new QiniuUtil();
    }

    public function execute() {
        // 获取状态为2(确认中)的直播
        $where[] = array('status', 2);
        $lives = $this->liveModel->getLiveList($where, 0, 1000);
        if (!empty($lives)) {
            // 获取用户的房间号
            $userIds = array();
            foreach ($lives as $live) {
                $userIds[] = $live['user_id'];
            }
            $roomInfos = $this->liveModel->checkLiveRoomByUserIds($userIds);
            // 检查已经直播30秒，已经断流的直播
            foreach ($lives as $live) {
                if ($live['start_time'] + 2 > time()) {
                    // 推流2秒后才开始检查直播流
                    continue;
                }
                // 获取直播流状态
                $status = $this->qiniuUtil->getRawStatus($live['service_liveid']);
                if (isset($status['framesPerSecond']['audio']) && isset($status['framesPerSecond']['video'])) {
                    if ($status['framesPerSecond']['audio'] > 0 && $status['framesPerSecond']['video'] > 0) {
                        // 音频、视频信号正常，设置直播状态为3(直播中)
                        $liveInfo[] = array('status', 3);
                        $this->liveModel->updateLiveById($live['id'], $liveInfo);
                        continue;
                    }
                    if (time() - $live['start_time'] < 120 && time() - $live['start_time'] > 10) {
                            //推流丢失音频或视频超过10秒钟，且在2分钟内的，发送消息告知主播
                            $content = "直播异常，请退出后重新开启";
                            continue;
                    }
                    if (time() - $live['start_time'] >= 120) {
                        //超2分钟，直接中断直播流
                        $this->liveService->endLive($live['user_id'], $roomInfos[$live['user_id']]['id'], $live['id'], $live['chat_room_id']);
                        $liveInfo[] = array('status', 7);
                        $this->liveModel->updateLiveById($live['id'], $liveInfo);
                    }
                }
            }
        }
    }
}
