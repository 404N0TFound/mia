<?php
namespace mia\miagroup\Daemon\Subject;

use \mia\miagroup\Data\Subject\Video as VideoData;
use \mia\miagroup\Service\Subject as SubjectService;

class Groupvideo extends \FD_Daemon {
    
    public function __construct(){
        date_default_timezone_set("Asia/Shanghai");
        set_time_limit(3600);
        ini_set('memory_limit','128M');
    }
    
    /**
     * 从七牛获取没有转码成功的视频
     */
    public function execute() {
        $qiniu = new \mia\miagroup\Util\QiniuUtil();
        //获取没有转码成功的视频
        $videoData = new VideoData();
        $subjectService = new SubjectService();
        $videoList = $videoData->getVideoList(array('status' => 2));
        if (!empty($videoList) && is_array($videoList)) {
            foreach ($videoList as $video) {
                if (empty($video['ext_info']['transcoding_pipe'])) {
                    continue;
                }
                //获取转码的状态
                $ret = $qiniu->getVideoPfopStatus($video['ext_info']['transcoding_pipe']);
                //更新转码结果
                $updateInfo['id'] = $video['id'];
                $updateInfo['subject_id'] = $video['subject_id'];
                if ($ret === true) {
                    $updateInfo['status'] = 1;
                    $updateInfo['subject_status'] = 1;
                    
                    $subjectService->updateSubjectVideo($updateInfo);
                } else {
                    $updateInfo['status'] = 3;
                    $subjectService->updateSubjectVideo($updateInfo);
                }
            }
        }
    }
}