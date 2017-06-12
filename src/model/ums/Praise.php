<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Praise extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //帖子赞表
    protected $tablePraise = 'group_subject_praises';
    
    /**
     * 查询赞数量
     */
    public function getPraiseCount($cond) {
        $this->tableName = $this->tablePraise;
        $where = array();
        
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result = $this->getRows($where, 'subject_uid,count(1) as nums', false, 0, false,false,'subject_uid');
        
        return $result;
    }
    
}