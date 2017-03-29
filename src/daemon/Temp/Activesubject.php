<?php 
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Service\Active as ActiveService;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;

class ActiveSubject extends \FD_Daemon {
    
    public function execute() {
        $this->setActiveSubjects();
        //$this->fixImgData();
    }
    
    public function fixImgData() {
        $activeData = new \mia\miagroup\Data\Active\Active();
        $data = $activeData->getRows([], 'id, top_img');
        foreach ($data as $v) {
            $id = $v['id'];
            $url = 'https://img.miyabaobei.com/' . $v['top_img'];
            @$img = getimagesize($url);
            if ($img) {
                $imgWidth = $img[0];
                $imgHeight = $img[1];
                $ext_info['image'] = [
                    'url' => $url,
                    'width' => $imgWidth,
                    'height' => $imgHeight
                ];
                $set_data['ext_info'] = json_encode($ext_info);
            }
            var_dump($set_data, $id);
            $activeData->updateActive($set_data, $id);
            exit;
        }
    }

    //将活动的帖子导入活动帖子关联表
    public function setActiveSubjects(){
        $activeService = new ActiveService();
        $subjectService = new SubjectService();
        $relationData = new RelationData();
        //获取所有在线活动
        $activeArrs = $activeService->getCurrentActive();
        if(!empty($activeArrs['data'])){
            foreach($activeArrs['data'] as $key=>$activeArr){
                //获取活动下的帖子
                $subjectArrs = $subjectService->getActiveSubjects($key, $type='all', false, 0)['data']['subject_lists'];
                if(empty($subjectArrs)){
                    continue;
                }
                foreach($subjectArrs as $subjectArr){
                    //将帖子信息存入活动帖子关联表中
                    $setData = array();
                    $setData['active_id'] = $activeArr['id'];
                    $setData['subject_id'] = $subjectArr['id'];
                    $setData['user_id'] = $subjectArr['user_id'];
                    $setData['create_time'] = date('Y-m-d H:i:s');
                    $relationData->addActiveSubjectRelation($setData);
                }
            }
        }
        return true;
    }
}