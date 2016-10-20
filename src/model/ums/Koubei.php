<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Koubei extends \DB_Query {

    protected $dbResource = 'miagroupums';

    protected $tableKoubei = 'koubei';
    protected $indexKoubei = array('id', 'item_id', 'user_id', 'rank_score', 'order_id', 'subject_id', 'create_time');
    
    /**
     * 查询口碑表数据
     */
    public function getKoubeiData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableKoubei;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexKoubei))) {
                $where[] = [':ge','created_time', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'start_time':
                        $where[] = [':ge','created_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created_time', $v];
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
        return $result;
    }
}