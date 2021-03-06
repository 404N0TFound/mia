<?php
namespace mia\miagroup\Daemon\Temp;

use mia\miagroup\Data\Active\Active as ActiveData;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Data\Active\ActiveSubjectRelation as RelationData;

class ActiveSubject extends \FD_Daemon {

    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
    }

    /**
     * 发送站内信
     */
    public function pushNews()
    {
        $serviceNews = new \mia\miagroup\Service\News();
        $sendFromUserId = 1026069;//线上蜜芽小天使账号1026069
        $handle = @fopen("/tmp/coupon_code", "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);//读行
                list($toUserId, $code) = explode("\t", $buffer, 2);
                if (empty($toUserId) || empty($code)) {
                    continue;
                }
                $content = "亲爱的蜜粉妈妈，恭喜获得2月晒单活动现金优惠券\n券码为：" . trim($code) . "\n请在我的优惠券中选择“领取优惠券”进行添加领取\n领取时间为4月30日前，使用有效期至5月30日24点，预期失效不再补发。";
                $serviceNews->addNews('single', 'group', 'coupon', $sendFromUserId, intval($toUserId), 0, $content);
            }
        }
    }
    /**
     * 发送站内信
     */
    public function pushNews1()
    {
        $serviceNews = new \mia\miagroup\Service\News();
        $sendFromUserId = 1026069;//线上蜜芽小天使账号1026069
        $handle = @fopen("/tmp/coupon_code1", "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);//读行
                list($toUserId, $code) = explode("\t", $buffer, 2);
                if (empty($toUserId) || empty($code)) {
                    continue;
                }
                $content = "亲爱的蜜粉妈妈\n恭喜获得“盘点宝宝吃过的#奶粉# ”活动现金优惠券\n券码为：" . trim($code) . "\n请在我的优惠券中选择“领取优惠券”进行添加领取\n领取时间为4月30日前，使用有效期至5月30日24点，预期失效不再补发。";
                $serviceNews->addNews('single', 'group', 'coupon', $sendFromUserId, intval($toUserId), 0, $content);
            }
        }
    }
    public function pushNews2()
    {
        $serviceNews = new \mia\miagroup\Service\News();
        $sendFromUserId = 1026069;//线上蜜芽小天使账号1026069
        $handle = @fopen("/tmp/coupon_code2", "r");
        if ($handle) {
            while (!feof($handle)) {
                $buffer = fgets($handle, 4096);//读行
                list($toUserId, $code) = explode("\t", $buffer, 2);
                if (empty($toUserId) || empty($code)) {
                    continue;
                }
                $content = "亲爱的蜜粉妈妈\n恭喜获得1月晒单活动现金优惠券\n券码为：" . trim($code) . "\n请在我的优惠券中选择“领取优惠券”进行添加领取\n领取时间为4月30日前，使用有效期至5月30日24点，预期失效不再补发。";
                $serviceNews->addNews('single', 'group', 'coupon', $sendFromUserId, intval($toUserId), 0, $content);
            }
        }
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
        $activeData = new ActiveData();
        $subjectService = new SubjectService();
        $relationData = new RelationData();
        //获取所有在线活动
        $activeArrs = $activeData->getBatchActiveInfos(false, 0);
        if(!empty($activeArrs)){
            foreach($activeArrs as $activeArr){
                //获取活动下的帖子
                $subjectArrs = $subjectService->getActiveSubjects($activeArr['id'], $type='all', 0, false, 0)['data']['subject_lists'];
                if(empty($subjectArrs)){
                    continue;
                }
                foreach($subjectArrs as $subjectArr){
                    //将帖子信息存入活动帖子关联表中
                    $setData = array();
                    $setData['active_id'] = $activeArr['id'];
                    $setData['subject_id'] = $subjectArr['id'];
                    $setData['user_id'] = $subjectArr['user_id'];
                    $setData['create_time'] = $subjectArr['created'];
                    $relationData->addActiveSubjectRelation($setData);
                }
            }
        }
        return true;
    }
    
    /**
     * 导入活动数据
     */
    public function importActiveData() {
        $relationData = new RelationData();
        $data = file('/home/hanxiang/label_subjects_daiyan');
        foreach ($data as $v) {
            $v = trim($v);
            list($user_id, $subject_id, $is_recommend, $create_time) = explode("\t", $v);
            
            //将帖子信息存入活动帖子关联表中
            $setData = array();
            $setData['active_id'] = 557;
            $setData['subject_id'] = $subject_id;
            $setData['user_id'] = $user_id;
            $setData['create_time'] = $create_time;
            $setData['is_recommend'] = $is_recommend;
            $relationData->addActiveSubjectRelation($setData);
        }
    }
}