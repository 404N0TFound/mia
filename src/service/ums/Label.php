<?php
namespace mia\miagroup\Service\Ums;

use mia\miagroup\Lib\Service;
use mia\miagroup\Model\Ums\Label as LabelModel;
use mia\miagroup\Service\Label as LabelService;
use mia\miagroup\Service\Subject as SubjectService;

class Label extends Service{
    
    private $labelModel;
    
    public function __construct(){
        parent::__construct();
        $this->labelModel = new LabelModel();
    }
    
    /**
     * 获取推荐和热门标签
     */
    public function getLabelInfoByPic($num = 20){
        $data = $this->labelModel->getLabelInfoByPic($num);
        return $this->succ($data);
    }
    
    /**
     * 获取关联信息
     * @param unknown $label_id
     * @param unknown $subject_id
     * @return unknown
     */
    public function getLabelRelation($subject_id){
        $label_id = array();
        $label_data = array();
        $labelRelation = new \mia\miagroup\Model\Ums\LabelRelation();
        $data = $labelRelation->getLabelRelation($subject_id);
        foreach($data as $k=>$v){
            $label_ids[] = $v['label_id'];
        }
        $label_info = $this->labelModel->getLabelInfo($label_ids);
        foreach($label_info as $k=>$v){
            $label_data[$v['id']] = $v['title'];
        }
        foreach($data as $k=>$v){
            $data[$k]['title'] = $label_data[$v['label_id']];
        }
        
        return $this->succ($data);
    }
    
    /**
     * ums标签下帖子列表
     */
    public function getLabelPicList($params) {
        $result = array('list' => array(), 'count' => 0);
        $condition = array();
        //初始化入参
        $orderBy = 'top_time DESC, recom_time DESC, create_time DESC';
        $limit = intval($params['limit']) > 0 && intval($params['limit']) < 100 ? $params['limit'] : 20;
        $offset = intval($params['page']) > 1 ? ($params['page'] - 1) * $limit : 0;
    
        if (intval($params['label_id']) > 0) {
            //标签ID
            $condition['label_id'] = $params['label_id'];
        }
        
        if ($params['is_recommend'] !== null && $params['is_recommend'] !== '' && in_array($params['is_recommend'], array(0, 1)) ) {
            //标签贴是否加精
            $condition['is_recommend'] = $params['is_recommend'];
        }
        if ($params['is_top'] !== null && $params['is_top'] !== '' && in_array($params['is_top'], array(0, 1)) ) {
            //标签贴是否置顶
            $condition['is_top'] = $params['is_top'];
        }
        
        if(empty($condition)){
            $condition['label_id'] = $params['id'];
        }
        
        $data = $this->labelModel->getLabelPicData($condition, $offset, $limit, $orderBy);
        if (empty($data['list'])) {
            return $this->succ($result);
        }
        $subjectIds = array();
        foreach ($data['list'] as $v) {
            $subjectIds[] = $v['subject_id'];
        }
        $subjectService = new SubjectService();
        $subjectInfos = $subjectService->getBatchSubjectInfos($subjectIds, 0, array('user_info','group_labels','item'), array())['data'];
        foreach ($data['list'] as $v) {
            $tmp = $v;
            $tmp['subject'] = $subjectInfos[$v['subject_id']];
            $result['list'][] = $tmp;
        }
        
        $result['count'] = $data['count'];
        return $this->succ($result);
    }

    
}