<?php
namespace mia\miagroup\Model\Ums;

class Label extends \DB_Query{
    
    protected $tableName = 'group_subject_label';
    protected $dbResource = 'miagroupums';
    
    public function getLabelInfoByPic($num=20){
        $limit = $num;
        $hotWhere[] = ['is_hot',1];
        $hotWhere[] = ['status',1];
        $hotOrderBy = 'hot_time DESC, id DESC';
        
        $recWhere[] = ['is_recommend',1];
        $recWhere[] = ['status',1];
        $recOrderBy = 'recom_time DESC, id DESC';
        $hotInfo    = $this->getRows($hotWhere,'*',$limit,0,$hotOrderBy);
        $recInfo    = $this->getRows($recWhere,'*',$limit,0,$recOrderBy);
        return array(
            'hot' => $hotInfo,
            'rec' => $recInfo,
        );
    }
    
    /**
     * 获取label信息
     */
    public function getLabelInfo($labelIds){
        $where[] = ['id',$labelIds];
        $data = $this->getRows($where);
        return $data;
    }
    
}