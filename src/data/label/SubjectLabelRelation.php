<?php
namespace mia\miagroup\Data\Label;

use Ice;

class SubjectLabelRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_label_relation';

    protected $mapping = array();
    // TODO
    
    /**
     * 根据帖子ID分组批量查标签ID
     */
    public function getBatchSubjectLabelIds($subjectIds) {
        if (empty($subjectIds)) {
            return array();
        }
        $where = array();
        $where[] = array(':in', 'subject_id', $subjectIds);
        $data = $this->getRows($where, '`subject_id`, `label_id`');
        $labelIdRes = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                if (!isset($labelIdRes[$v['subject_id']][$v['label_id']])) {
                    $labelIdRes[$v['subject_id']][$v['label_id']] = $v['label_id'];
                }
            }
        }
        return $labelIdRes;
    }

    /**
     * 保存蜜芽圈标签关系记录
     *
     * @param array $labelRelationInfo
     *            图片标签关系信息
     * @return bool
     */
    public function saveLabelRelation($labelRelationInfo) {
        $insertLabel = $this->insert($labelRelationInfo);
        return $insertLabel;
    }


    
    /**
     * 根据标签ID获取帖子列表
     */
    public function getSubjectListByLableIds($lableIds,$offset,$limit,$is_recommend=0)
    {
        if(!is_array($lableIds)){
            return [];
        }

        $where[] = ['label_id',$lableIds];
        $where[] = ['status',1];
        $orderBy = 'subject_id DESC';
        if($is_recommend>0){
            $where[] = ['is_recommend',1];
            $orderBy = 'top_time DESC, recom_time DESC';
        }
        $data = $this->getRows($where,'subject_id',$limit,$offset,$orderBy);
        $result = [];
        foreach ($data as $key => $value) {
            $result[$value['subject_id']] = $value['subject_id'];
        }
        return $result;
    }

    /**
     * 批量获取标签下的精华帖子是否置顶
     */
    public function getLableSubjectsTopStatus($lableId, $subjectIds)
    {
        if (empty($subjectIds) || empty($lableId)) {
            return array();
        }
        $where[] = ['status',1];
        $where[] = ['label_id',$lableId];
        $where[] = ['subject_id',$subjectIds];
        $data = $this->getRows($where);
        $result = array();
        foreach ($data as $v) {
            $result[$v['subject_id']] = $v['is_top'];
        }
        return $result;
    }
    
    /**
     * 获取标签下是否有精选帖子
     */
    public function getLabelIsRecommendInfo($labelId){
        $where[] = ['label_id',$labelId];
        $where[] = ['status',1];
        $where[] = ['is_recommend',1];
        $result = $this->getRow($where);
        return $result;
    }
    
    /**
     * 获取关联信息
     * @param unknown $label_id
     * @param unknown $subject_id
     * @return unknown
     */
    public function getLabelRelation($subject_id,$label_id){
        $where[] = ['label_id',$label_id];
        $where[] = ['subject_id',$subject_id];
        $data = $this->getRow($where);
        return $data;
    }
    
    /**
     * 添加标签帖子关系表
     * @param unknown $label_id
     * @param unknown $subject_id
     * @param unknown $is_recommend
     * @param unknown $user_id
     */
    public function addLabelRelation($subject_id,$label_id,$is_recommend,$user_id){
        $setData = array(
            'label_id'              =>  $label_id,
            'subject_id'            =>  $subject_id,
            'is_recommend'          =>  $is_recommend,//标签下的精华贴子
            'recom_time'            =>  date('Y-m-d H:i:d',time()),
            'create_time'           =>  date('Y-m-d H:i:d',time()), //添加时间
            'user_id'               =>  0,  //0 为后台创建者
            'status'                =>  1,
            'operator'              =>  $user_id,
        );
        $insert_id = $this->insert($setData);
        return $insert_id;
    }
    
    /**
     * 给标签下的帖子  加精
     * @param int $user_id 操作人id
     */
    public function setLabelRelationRecommend($subject_id, $label_id, $recommend, $user_id = 0){
        if($recommend == 1){
            $date = date('Y-m-d H:i:s',time());
        }else{
            $date = "0000-00-00 00:00:00";
        }
        $where[] = ['subject_id',$subject_id];
        $where[] = ['label_id',$label_id];
        $setData[] = ['is_recommend',$recommend];
        $setData[] = ['operator',$user_id];
        $setData[] = ['recom_time',$date];
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * 给标签下的帖子  置顶
     * @param int $user_id 操作人id
     */
    public function setLabelRelationTop($subject_id, $label_id, $top, $user_id = 0){
        if($top == 1){
            $date = date('Y-m-d H:i:s',time());
        }else{
            $date = "0000-00-00 00:00:00";
        }
        $where[] = ['subject_id',$subject_id];
        $where[] = ['label_id',$label_id];
        $setData[] = ['is_top',$top];
        $setData[] = ['operator',$user_id];
        $setData[] = ['top_time',$date];
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * @todo 删除图片和标签的对应关系
     * @param label_id, subject_id
     * @return 返回影响的行数
     **/
    public function removeRelation($subject_id,$label_id){
        $where[] = ['subject_id',$subject_id];
        $where[] = ['label_id',$label_id];
        $affect = $this->delete($where);
        return $affect;
    }
    
}
