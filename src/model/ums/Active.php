<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miagroupums';

    protected $tableActive = 'group_active';
    protected $tableActiveSubjectRelation = 'group_subject_active_relation';


    public function getGroupActiveData($month)
    {
        $this->tableName = $this->tableActive;
        $time = date("Y-m-d", strtotime("-".$month." month"));
        $where[] = ['status',1];
        $field = "id, title";
        $where[] = [':gt', 'created', $time];
        $res = $this->getRows($where, $field);
        return $res;
    }
    
    /**
     * 获取活动图片列表
     */
    public function getActiveSubjectList($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableActiveSubjectRelation;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','create_time', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        if (!empty($result['list'])) {
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k] = $v;
            }
        }
        return $result;
    }
}