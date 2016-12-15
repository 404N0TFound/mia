<?php
namespace mia\miagroup\Daemon\Live;

use \mia\miagroup\Data\Live\Live as LiveData;
use mia\miagroup\Data\Live\LiveRoom as LiveRoom;
use mia\miagroup\Service as Service;
use \mia\miagroup\Model\Live as LiveModel;

/**
 * 直播结束两小时后，清空房间配置信息
 * 所以直播配置不要提前2小时进行
 * @author user
 *
 */
class Cleanlivesetting extends \FD_Daemon
{
    public function execute()
    {
        $liveMod = new LiveModel();
        $liveService = new Service\Live();
        $roomNum = $liveMod->getLiveRoomNum();

        $limit = 100;
        $end = floor($roomNum / $limit) + 1;
        for ($i = 1; $i <= $end; $i++) {
            $roomList = $liveMod->getLiveRoomList($i, $limit);//非直播中的房间列表
            //根据latest_live_id寻找对应的直播,根据直播结束时间判断setting的清空
            $id_arr = [];
            foreach ($roomList as $roomInfo) {
                $id_arr[] = intval($roomInfo['latest_live_id']);
            }
            //latest_live_id寻找对应的直播
            $liveList = $liveMod->getLiveList(['id' => ['id', $id_arr], 'end_time' => [':lt', 'end_time', date("Y-m-d H:i:s", time() - 3600 * 2)]], 0, 100);

            foreach ($liveList as $live) {
                if (!empty($live['settings'])) {
                    continue;
                }
                $setting = json_decode($roomList[$live['user_id']]['settings'], true);
                $trans = [];
                $check = 0;
                //转移
                if(array_key_exists('redbag', $setting)) {
                    $trans['redbag'] = $setting['redbag'];
                    unset($setting['redbag']);
                    $check = 1;
                }
                if(array_key_exists('coupon', $setting)) {
                    $trans['coupon'] = $setting['coupon'];
                    unset($setting['coupon']);
                    $check = 1;
                }
                if(array_key_exists('banners', $setting)) {
                    $trans['banners'] = $setting['banners'];
                    unset($setting['banners']);
                    $check = 1;
                }
                if(array_key_exists('share', $setting)) {
                    $trans['share'] = $setting['share'];
                    unset($setting['share']);
                    $check = 1;
                }
                //清空
                if(array_key_exists('user_num', $setting)) {
                    unset($setting['user_num']);
                    $check = 1;
                }
                if(array_key_exists('push_time', $setting)) {
                    unset($setting['push_time']);
                    $check = 1;
                }
                if($check == 0){
                    continue;
                }
                //修改房间设置
                $liveService->updateLiveRoomSettings($roomList[$live['user_id']]['id'],$setting);
                //live表增加设置
                if(!empty($trans)) {
                    $liveMod->updateLiveInfo(['id',$live['id']],[['settings',json_encode($trans)]]);
                }
            }
        }
    }
}
