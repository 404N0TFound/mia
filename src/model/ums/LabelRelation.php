<?php
namespace mia\miagroup\Model\Ums;

class LabelRelation extends \DB_Query{
    
    protected $tableName = 'group_subject_label_relation';
    protected $dbResource = 'miagroupums';
    
    public $tableGroupSubjects = 'group_subjects';
    public $tableGroupSubjectLabel = 'group_subject_label';
    
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
    public function setLabelRelationRecommend($id,$is_recommend,$user_id){
        $where[] = ['id',$id];
        $setData[] = ['is_recommend',$is_recommend];
        $setData[] = ['operator',$user_id];
        $setData[] = ['recom_time','now()'];
        $affect = $this->update($setData,$where);
        return $affect;
    }
    
    /**
     * @todo 获取图片已经打标信息
     * @param subject_id 蜜芽圈图片id
     * @return 获取得到图片对应的标签列表信息  和  信息的总数量
     **/
    public function getExistsLabel($subject_id)
    {
        $sql = 'SELECT gs.id AS subject_id, gsl.title AS label_title, gslr.is_recommend AS is_recommend, gsl.id AS label_id, gslr.id AS relation_id FROM ' . $this->tableGroupSubjects . ' AS gs
    
                LEFT JOIN ' . $this->tableName . ' AS gslr
    
                ON (gslr.subject_id = gs.id)
    
                LEFT JOIN ' . $this->tableGroupSubjectLabel . ' AS gsl
    
                ON (gsl.id = gslr.label_id)
    
                WHERE gs.id = ' . $subject_id;
        $result = $this->query($sql);
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