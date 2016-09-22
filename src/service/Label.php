<?php
namespace mia\miagroup\Service;

use mia\miagroup\Model\Label as LabelModel;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Util\NormalUtil;
class Label extends \mia\miagroup\Lib\Service {

    public $labelModel = null;

    public function __construct() {
        parent::__construct();
        $this->labelModel = new LabelModel();
    }

    /**
     * 根据帖子ID批量分组获取标签信息
     */
    public function getBatchSubjectLabels($subjectIds) {
        $labelModel = new \mia\miagroup\Model\Label();
        $subjectLabels = $labelModel->getBatchSubjectLabels($subjectIds);
        return $this->succ($subjectLabels);
    }

    /**
     * 保存蜜芽圈标签关系记录
     */
    public function saveLabelRelation($labelRelationInfo) {
        $data = $this->labelModel->saveLabelRelation($labelRelationInfo);
        return $this->succ($data);
    }

    /**
     * 保存蜜芽圈标签
     */
    public function addLabel($labelTitle) {
        $labelResult = $this->labelModel->checkIsExistByLabelTitle($labelTitle);
        if (empty($labelResult)) {
            // 如果没有存在，则保存该自定义标签
            $insertId = $this->labelModel->addLabel($labelTitle);
        } else {
            $insertId = $labelResult['id'];
        }
        return $this->succ($insertId);
    }
    
    /**
     * 批量获取标签信息
     */
    public function getBatchLabelInfos($labelIds){
        if (empty($labelIds)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->getBatchLabelInfos($labelIds);
        return $this->succ($data);
    }
    
    /**
     * 获取我关注的所有标签
     */
    public function getAllAttentLabel($userId,$page=1,$count=10)
    {
        $labelIds = $this->labelModel->getLabelListByUid($userId);
        $labelInfos = $this->getBatchLabelInfos($labelIds)['data'];
        return $this->succ(array_values($labelInfos));
    }
    
    /**
     * 批量获取标签下的帖子
     */
    public function getBatchSubjectIdsByLabelIds($labelIds,$currentUid=0,$page=1,$limit=10,$is_recommend=0)
    {
        if(!is_array($labelIds)){
            return $this->succ(array());
        }
        $subjectIds = $this->labelModel->getSubjectListByLableIds($labelIds,$page,$limit,$is_recommend);
        $subjectService = new SubjectService();
        $data = $subjectService->getBatchSubjectInfos($subjectIds,$currentUid,array('user_info', 'count', 'comment', 'group_labels', 'praise_info'))['data'];
        return $this->succ($data);
    }
    
    /**
     * 关注标签
     */
    public function focusLabel($userId, $labelIds) {
        if (empty($labelIds) || empty($userId)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->addLableRelation($userId, $labelIds);
        return $this->succ($data);
    }
    
    /**
     * 取消关注标签
     */
    public function cancelFocusLabel($userId, $labelId) {
        if (empty($labelId) || empty($userId)) {
            return $this->succ(array());
        }
        $data = $this->labelModel->removeLableRelation($userId,$labelId);
        return $this->succ($data);
    }
    
    /**
     * 获取全部归档标签
     */
    public function getArchiveLalbels() {
        $categoryLabels = $this->labelModel->getCategoryLables();
        if (empty($categoryLabels)) {
            return array();
        }
        $labelIds = array();
        $categoryIds = array();
        foreach ($categoryLabels as $labels) {
            $labelIds[] = $labels['id'];
            $categoryIds[] = $labels['category_id'];
        }
        //获取归档信息
        $categoryInfos = $this->labelModel->getLabelByCategroyIds($categoryIds);
        if (empty($categoryInfos)) {
            return array();
        }

        //获取标签信息
        $labelInfos = $this->getBatchLabelInfos($labelIds)['data'];

        //拼装结果集
        $result = array();
        foreach ($categoryLabels as $labels) {
            if (!isset($result[$labels['category_id']]) && isset($categoryInfos[$labels['category_id']])) {
                $result[$labels['category_id']] = $categoryInfos[$labels['category_id']];
            }
            if (isset($categoryInfos[$labels['category_id']]) && isset($labelInfos[$labels['id']])) {
                $result[$labels['category_id']]['labels'][] = $labelInfos[$labels['id']];
            }
        }
        return $this->succ(array_values($result));
    }
    
    /**
     * 获取新人推荐标签
     */
    public function getNewUserRecommendLabels($page=1,$count=10) {
        $labelIds = $this->labelModel->getRecommendLables($page,$count,'is_new');
        $labelInfos = $this->getBatchLabelInfos($labelIds)['data'];
        return $this->succ(array_values($labelInfos));
    }
    
    /**
     * 获取全部推荐标签
     */
    public function getRecommendLabels($page=1,$count=10) {
        $labelIds = $this->labelModel->getRecommendLables($page,$count,'is_recommend');
        $labelInfos = $this->getBatchLabelInfos($labelIds)['data'];
        return $this->succ(array_values($labelInfos));
    }

    /**
     * 标签详情
     */
    public function getBatchLabelDetail($labelId,$userId = 0, $fields = array('share_info'))
    {
        if(empty($labelId)){
            return $this->succ(array());
        }
        $label = $this->labelModel->getBatchLabelInfo($labelId);
        if (empty($label)) {
            return $this->succ(array());
        }
        //标签的关联标签
        $labelRelate = $this->labelModel->getRelateLabels($labelId);

        //关注信息
        if (intval($userId) > 0) {
            $relate = $this->labelModel->getLableRelationByUserId($userId, $labelId);
            $isFocus = isset($relate['status']) && $relate['status'] == 1 ? 1 : 0;
        }

        //大图信息
        if (!empty($label['hot_pic']) && $label['is_recommend'] == 1) {
            if (!empty($label['ext_info'])) {
                $extInfo = json_decode($label['ext_info'], true);
                $imgWidth = $extInfo['pic_width'];
                $imgHeight = $extInfo['pic_height'];
                $imgUrl = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $label['hot_pic'];
                $rec_pic = array('pic' => array('url' => strval($imgUrl), 'width' => $imgWidth, 'height' => $imgHeight));
            } else {
                @$img = getimagesize(\F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $label['hot_pic']);
                if ($img) {
                    $imgWidth = $img[0];
                    $imgHeight = $img[1];
                    $setData['ext_info'] = [
                        'pic_width' => $imgWidth,
                        'pic_height' => $imgHeight
                    ];
                    $this->labelModel->updateLabelImgInfo($id, $imgWidth, $setData);
                    $imgUrl = \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $label['hot_pic'];
                    $rec_pic = array('pic' => array('url' => strval($imgUrl), 'width' => $imgWidth, 'height' => $imgHeight));
                } else {
                    $rec_pic = (object)array();
                }
            }
        } else {
            $rec_pic = (object)array();
        }
        
        $labelBaseInfo = array(
            'id'            => $label['id'],
            'title'         => $label['title'],
            'rec_pic'       => $rec_pic,
            'rec_small_pic' => (strlen($label['hot_small_pic']) > 0 && $label['is_recommend'] == 1) ? \F_Ice::$ins->workApp->config->get('app')['url']['img_url'] . $label['hot_small_pic'] : '',
            'is_hot'        => intval($label['is_hot']),
            'is_focused'    => intval($isFocus),
        );
        if (in_array('share_info', $fields)) {
            // 分享内容
            $shareConfig = \F_Ice::$ins->workApp->config->get('busconf.subject');
            $shareDefault = $shareConfig['defaultShareInfo']['label'];
            $share = $shareConfig['groupShare'];
            $shareTitle = $shareDefault['title'];
            $shareDesc = sprintf($shareDefault['desc'],$label['title']);
            $shareImage = $shareDefault['img_url'];
            $h5Url = sprintf($shareDefault['wap_url'], $label['id']);

            // 替换搜索关联数组
            $replace = array('{|title|}' => $shareTitle, '{|desc|}' => $shareDesc, '{|image_url|}' => $shareImage, '{|wap_url|}' => $h5Url, '{|extend_text|}' => $shareDefault['extend_text']);
            // 进行替换操作
            foreach ($share as $keys => $sh) {
                $share[$keys] = NormalUtil::buildGroupShare($sh, $replace);
            }
            
            $labelBaseInfo['share_info'] = $share;
        }

        $result = array(
            'label_info'       => $labelBaseInfo,
            'relate_labels'    => array_values($labelRelate),
        );

        return $this->succ($result);
    }


    /**
     * 标签图片（包括全部和精品图片）
     */
    public function getLableSubjects($labelId,$page=1,$count=10,$isRecommend=0)
    {
        if(empty($labelId)){
            return $this->succ(array());
        }
        $activeSujectInfo = $this->getBatchSubjectIdsByLabelIds([$labelId],0,$page,$count,$isRecommend)['data'];
        $subjectIds = array_keys($activeSujectInfo);
        if ($isRecommend == 1) {
            //如果是精华帖需要检测帖子的置顶状态
            $topStatus = $this->labelModel->getLableSubjectsTopStatus($labelId, $subjectIds);
            foreach($activeSujectInfo as $key => $subjectInfo){
                $activeSujectInfo[$key]['is_top'] = $topStatus[$subjectInfo['id']];
            }
        } else {
            foreach($activeSujectInfo as $key => $subjectInfo){
                unset($activeSujectInfo[$key]['is_top']);
            }
        }

        return $this->succ(array_values($activeSujectInfo));
    }
}
