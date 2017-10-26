<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Active extends \DB_Query {

    protected $dbResource = 'miagroupums';

    protected $tableActive = 'group_active';
    protected $tableActiveSubjectRelation = 'group_subject_active_relation';
    protected $tableSubjectPointTags = 'group_subject_point_tags';
    protected $tableActiveItemTab = 'group_active_item_tab';

    public function getGroupActiveData($month = 1, $status = NULL)
    {
        $this->tableName = $this->tableActive;
        $time = date("Y-m-d", strtotime("-".$month." month"));
        if(!isset($status)) {
            $where[] = ['status',[1,-1]];
        }else{
            $where[] = ['status',$status];
        }
        $field = "id, title, status, end_time";
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
    
    /**
     * 根据商品id获取参加活动的帖子
     */
    public function getActiveSubjectByItem($cond, $offset = 0, $limit = 50, $orderBy = 'id desc') {
        $this->tableName = $this->tableActiveSubjectRelation;
        $orderBy = $this->tableName. '.id desc';
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
                    case 'item_id':
                        $where[] = ['pt.item_id', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        
        $join = 'left join '.$this->tableSubjectPointTags. ' as pt on ' .$this->tableName . '.subject_id=pt.subject_id ';
        $field = "pt.item_id, count(pt.item_id) as count";
        $group_by = 'pt.item_id';

        $data = $this->getRows($where, $field, $limit, $offset, $orderBy, $join, $group_by);
        if (empty($data)) {
            return array();
        }
        foreach ($data as $v) {
            $result[$v['item_id']] = $v['count'];
        }
        return $result;
    }
    
    /**
     * 获取活动商品标签表数据
     **/
    public function getActiveTabItemInfos($active_id, $cond, $limit = 20, $offset = 0)
    {
        $this->tableName = $this->tableActiveItemTab;
        $result = array();
        $where = [];
        $where[] = ['active_id', $active_id];
        if(empty($active_id)) {
            return [];
        }
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
        $field = '*';
        $orderBy = 'id desc';
        $res = $this->getRows($where, $field, $limit, $offset, $orderBy);
        return $res;
    }
    
    /**
     * 查询消消乐活动商品帖子情况
     */
    public function getXiaoxiaoleItemSubjectCount($cond, $offset = 0, $limit = false) {
        if (intval($cond['active_id']) <= 0) {
            return false;
        }
        if (!isset($cond['status'])) {
            $cond['status'] = 1;
        }
        
        $this->tableName = $this->tableActiveSubjectRelation . ' as ar';
        $where = [];
        $where[] = [':literal', 'pt.item_id = it.item_id'];
        $having = [];
        $order_by = false;
        
        //组装where条件
        foreach ($cond as $k => $v) {
            switch ($k) {
                case 'active_id':
                    $where[] = [':eq','ar.active_id', $v];
                    break;
                case 'item_tab':
                    $where[] = ['it.item_tab', $v];
                    break;
                case 'is_pre_set':
                    $where[] = ['it.is_pre_set', $v];
                    break;
                case 'start_time':
                    $where[] = [':ge','ar.create_time', $v];
                    break;
                case 'end_time':
                    $where[] = [':le','ar.create_time', $v];
                    break;
                case 'min_subject_num':
                    $having[] = "subject_count >= {$v}";
                    break;
                case 'max_subject_num':
                    $having[] = "subject_count <= {$v}";
                    break;
                case 'min_koubei_num':
                    $having[] = "koubei_count >= {$v}";
                    break;
                case 'max_koubei_num':
                    $having[] = "koubei_count <= {$v}";
                    break;
            }
        }
        $join = [
            'INNER JOIN group_subject_point_tags as pt on ar.subject_id = pt.subject_id',
            'LEFT JOIN koubei as k on pt.subject_id = k.subject_id',
            'RIGHT JOIN group_active_item_tab as it on ar.active_id = it.active_id'
        ];
        $field = "pt.item_id, COUNT(DISTINCT(pt.id)) as subject_count, COUNT(DISTINCT(k.id)) as koubei_count";
        $group_by = 'pt.item_id';
        
        $data = $this->getRows($where, $field, $limit, $offset, $order_by, $join, $group_by, $having);
        if (empty($data)) {
            return array();
            
        }
        foreach ($data as $v) {
            $result[$v['item_id']] = $v;
        }
        return $result;
    }
}