<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Model\Ums\Koubei as KoubeiModel;
use mia\miagroup\Model\Ums\User as UserModel;
use mia\miagroup\Model\Ums\Item as ItemModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Util\EmojiUtil;
use mia\miagroup\Model\Koubei as KoubeiApiModel;
use mia\miagroup\Model\Ums\Subject as SubjectUmsModel;

class Koubei extends \mia\miagroup\Lib\Service {
    
    private $koubeiModel;
    private $userModel;
    private $itemModel;
    
    public function __construct() {
        $this->koubeiModel = new KoubeiModel();
        $this->userModel = new UserModel();
        $this->emojiUtil = new EmojiUtil();
        $this->itemModel = new ItemModel();
    }
    
    /**
     * ums口碑列表
     */
    public function getKoubeiList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'id desc'; //默认排序
        $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 50;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
        
        if (intval($params['id']) > 0) {
            //口碑ID
            $condition['id'] = $params['id'];
        }
        if (intval($params['user_id']) > 0 && intval($condition['id']) <= 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0 && intval($condition['id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['status'] !== null && $params['status'] !== '' && in_array($params['status'], array(0, 1, 2)) && intval($condition['id']) <= 0) {
            //口碑状态
            $condition['status'] = $params['status'];
        }
        if ($params['rank'] !== null && $params['rank'] !== '' && in_array($params['rank'], array(0, 1)) && intval($condition['id']) <= 0) {
            //是否是精品
            $condition['rank'] = $params['rank'];
        }
        if ($params['score'] !== null && $params['score'] !== '' && in_array($params['score'], array(0, 1, 2, 3, 4, 5)) && intval($condition['id']) <= 0) {
            //用户评分
            $condition['score'] = $params['score'];
        }
        if ($params['score'] == '机选差评' && intval($condition['id']) <= 0) {
            //机器评分
            $condition['machine_score'] = 1;
        }
        if (intval($params['item_id']) > 0 && intval($condition['id']) <= 0) {
            //商品ID
            $condition['item_id'] = $params['item_id'];
            $orderBy = 'rank_score desc'; //按分数排序，与app保持一致
        }
        if (!empty($params['brand_name']) && intval($condition['item_id']) <= 0 && intval($condition['id']) <= 0) {
            //品牌ID
            $brandId = intval($this->itemModel->getBrandIdByName($params['brand_name']));
            $itemIds = $this->itemModel->getAllItemByBrandId($brandId);
            if (!empty($itemIds)) {
                $condition['id'] = $itemIds;
            }
        }
        if (intval($params['category_id']) > 0 && empty($params['brand_name']) && intval($condition['item_id']) <= 0 && intval($condition['id']) <= 0) {
            //类目ID
            $itemIds = $this->itemModel->getAllItemByCategoryId($params['category_id']);
            if (!empty($itemIds)) {
                $condition['id'] = $itemIds;
            }
        }
        if (strtotime($params['start_time']) > 0 && intval($condition['id']) <= 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0 && intval($condition['id']) <= 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        $data = $this->koubeiModel->getKoubeiData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * ums口碑相关蜜芽贴列表
     */
    public function getKoubeiSubjectList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'create_time desc'; //默认排序
        $limit = (intval($params['limit'] > 0) && intval($params['limit'] < 100)) ? $params['limit'] : 50;
        $offset = intval($params['page'] > 1) ? (($params['page'] - 1) * limit) : 0;
    
        if (intval($params['subject_id']) > 0) {
            //帖子ID
            $condition['subject_id'] = $params['subject_id'];
        }
        if (intval($params['user_id']) > 0) {
            //用户id
            $condition['user_id'] = $params['user_id'];
        }
        if (!empty($params['user_name']) && intval($condition['user_id']) <= 0) {
            //用户名
            $condition['user_id'] = intval($this->userModel->getUidByUserName($params['user_name']));
        }
        if (!empty($params['nick_name']) && intval($condition['user_id']) <= 0) {
            //用户昵称
            $condition['user_id'] = intval($this->userModel->getUidByNickName($params['nick_name']));
        }
        if ($params['is_audited'] !== null && $params['is_audited'] !== '') {
            //帖子审核状态
            $condition['is_audited'] = $params['is_audited'];
        }
        if (intval($params['item_id']) > 0) {
            //商品ID
            $condition['item_id'] = $params['item_id'];
        }
        if (strtotime($params['start_time']) > 0) {
            //起始时间
            $condition['start_time'] = $params['start_time'];
        }
        if (strtotime($params['end_time']) > 0) {
            //结束时间
            $condition['end_time'] = $params['end_time'];
        }
        
        $data = $this->koubeiModel->getSubjectData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info', 'item', 'album'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        $result['count'] = $data['count'];
        return $this->succ($result);
    }
    
    /**
     * 蜜芽贴同步到口碑
     * @param $subjectData array() 蜜芽贴信息
     */
    public function setSubjectToKoubei($subjectData){
        //1、判断该蜜芽贴是否同步到口碑贴
        $koubeiInfo = $this->koubeiModel->getKoubeiBySubjectId($subjectData['id'],$subjectData['item_id']);
        if(!empty($koubeiInfo)){
            //（1）如果同步过，则直接返回同步过的口碑id
            return $this->succ($koubeiInfo['id']);
        }else{
            //（2）如果没有同步过，则同步为口碑贴
            //组装插入口碑信息  ####start
            $koubeiSetData = array();
            $koubeiSetData['status'] = 2;
            $koubeiSetData['title'] = (isset($subjectData['title']) ) ? trim($subjectData['title']) : "";
            $koubeiSetData['content'] = trim($this->emojiUtil->emoji_unified_to_html($subjectData['text']));
            $koubeiSetData['item_id'] = (isset($subjectData['item_id']) && intval($subjectData['item_id']) > 0) ? intval($subjectData['item_id']) : 0;
            $koubeiSetData['user_id'] = $subjectData['user_id'];
            $koubeiSetData['subject_id'] = (isset($subjectData['id'])) ? trim($subjectData['id']) : '';
            $koubeiSetData['created_time'] = date('Y-m-d H:i:s',time());
            
            $koubeiSetData['immutable_score'] = $this->calImmutableScore($subjectData);
            $koubeiSetData['rank_score'] = $subjectData['immutable_score'] + 12 * 0.5;
            
            $labels = array();$labels['label'] = array();$labels['image'] = array();
            if(!empty($subjectData['group_labels']))
            {
                foreach($subjectData['group_labels'] as $label)
                {
                    $labels['label'][] = $label['title'];
                }
            }
            if(!empty($subjectData['image_infos']))
            {
                foreach($subjectData['image_infos'] as $image)
                {
                    $imageInfo = array();
                    $url = parse_url($image['url']);
                    $imageInfo['url'] = ltrim($url['path'],'/');
                    $imageInfo['width'] = $image['width'];
                    $imageInfo['height'] = $image['height'];
                    
                    $labels['image'][] = $imageInfo;
                }
            }
            $koubeiSetData['extr_info'] = json_encode($labels);
            //####end
            $mKoubei = new KoubeiApiModel();
            $koubeiInsertId = $mKoubei->saveKoubei($koubeiSetData);
            
            if(!$koubeiInsertId)
            {
                return $this->error(6101);
            }
            
        }
        
//         //2、判断该蜜芽贴是否同步到口碑蜜芽贴
//         $koubeiSubject = $this->koubeiModel->getKoubeiSubjectBySubjectId($subjectData['id']);
//         $koubeiSubjectData = array();
//         if(!empty($koubeiSubject)){
            //（1）如果同步过，则更新口碑蜜芽贴中的通过状态为通过
            $koubeiSubjectData = array('is_audited'=>1);
            $this->koubeiModel->updateKoubeiSubject($koubeiSubjectData,$koubeiSubject['id']);
//         }else{
//             //（2）如果没同步过，就同步到口碑贴中
//             $koubeiSubjectData['subject_id'] = $subjectData['id'];
//             $koubeiSubjectData['user_id'] = $subjectData['user_id'];
//             $koubeiSubjectData['is_audited'] = 1;
//             $koubeiSubjectData['create_time'] = $subjectData['created'];
//             $this->koubeiModel->saveKoubeiSubject($koubeiSubjectData);
//         }
        
        //3、如果蜜芽贴图片不为空，则同步到口碑图片中
        if(!empty($subjectData['image_infos'])){
            foreach ($subjectData['image_infos'] as $path) {
                $koubeiPicData = array();
                if (!empty($path)) {
                    $url = parse_url($path['url']);
                    $url = ltrim($url['path'],'/');
                    $ss = explode( ".", $url);
                    $koubeiPicData = array(
                        "koubei_id"	=> $koubeiInsertId,
                        "local_url"	=> $url,
                        "local_url_origin"=> $url,
                        "local_url_big"=> $ss[0]."_big.".$ss[1]);
                    $mKoubei->saveKoubeiPic($koubeiPicData);
                }
            }
        }
        //4、如果蜜芽贴关联了商品，将商品信息同步到口碑蜜芽贴商品关联表中
//         if(!empty($subjectData['items'])){
//             foreach($subjectData['items'] as $item){
//                 $koubeItemData = array();
//                 if(empty($item)){
//                     continue;
//                 }
//                 $koubeItemData['subject_id'] = $subjectData['id'];
//                 $koubeItemData['item_id'] = $item['item_id'];
//                 $this->koubeiModel->saveKoubeiSubjectItem($koubeItemData);
//             }
//         }
        //将口碑id回写到帖子表中的扩展数据中
        if($koubeiInsertId > 0 && $subjectData['id'] > 0){
            $mSubject = new SubjectUmsModel();
            $koubeiInfo = array();
            $koubeiInfo['id'] = $koubeiInsertId;
            $koubeiInfo['image'] = $subjectData['image_infos'];
            $mSubject->addKoubeiIdToSubject($koubeiInfo,$subjectData['id']);
        }
    
        return $this->succ($koubeiInsertId);
    }
    
    //获取口碑的不变分数
    private function calImmutableScore($data)
    {
        //初始分，时间月份 * 权重
        $immutable_score = 0;
    
        //图片分，有图10分，权重0.3
        $hasPic = 0;
        if(!empty($data['image_infos'])) {
            $hasPic = 1;
        }
        $immutable_score += (0.3 * 10 * $hasPic);
    
        //文本长度分，100字以上10分，50字以上8分，30字以上5分，10字以上3分，10字以下1分，权重0.2
        $content_count = mb_strlen($data['text'],'utf-8');
        if($content_count > 100) {
            $immutable_score += (10 * 0.2);
        }
        else if($content_count > 50) {
            $immutable_score += (8 * 0.2);
        }
        else if($content_count > 30) {
            $immutable_score += (5 * 0.2);
        }
        else if($content_count > 10) {
            $immutable_score += (3 * 0.2);
        }
    
        //口碑评分，权重1
        $immutable_score += ($data['score'] * 1.5);
    
        return $immutable_score;
    }
    
}