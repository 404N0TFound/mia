<?php
namespace mia\miagroup\Model\Ums;

class Label extends \DB_Query{
    
    protected $tableName = 'group_subject_label';
    protected $dbResource = 'miagroupums';
    
    public function getLabelInfoByPic($num=20){
        $limit = $num;
        $hotWhere[] = ['is_hot',1];
        $hotOrderBy = 'hot_time DESC, id DESC';
        
        $recWhere[] = ['is_recommend',1];
        $recOrderBy = 'recom_time DESC, id DESC';
        $hotInfo    = $this->getRows($hotWhere,'*',$limit,0,$hotOrderBy);
        $recInfo    = $this->getRows($recWhere,'*',$limit,0,$recOrderBy);
        return array(
            'hot' => $hotInfo,
            'rec' => $recInfo,
        );
    }
    
    /**
     * 保存蜜芽圈标签
     *
     * @param array $labelInfo
     *            标签信息
     * @return int 标签id
     */
    public function addLabel($labelTitle,$userId) {
        $labelTitleMd5 = md5($labelTitle);
        $setData = ['title' => $labelTitle, 'title_md5' => $labelTitleMd5,'user_id'=>$userId];
        $LabelId = $this->insert($setData);
    
        return $LabelId;
    }
    
    /**
     * @todo 删除标签
     * @param label_id, subject_id
     * @return 返回影响的行数
     **/
    public function removeLabelByLabelId($label_id)
    {
        $where[] = ['id',$label_id];
        $affect = $this->delete($where);
        return $affect;
    }
    
}