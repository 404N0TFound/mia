<?php
namespace mia\miagroup\Data\Koubei;

use Ice;

class KoubeiTagsRelation extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'koubei_tags_relation';

    protected $mapping = array();

    /**
     * 添加关系记录
     */
    public function addTagsRealtion($insertData)
    {
        $result = $this->insert($insertData);
        return $result;
    }

    /**
     * 获取标签信息
     */
    public function getTags($where, $cols = '*', $limit = FALSE, $offset = 0, $orderBy = FALSE, $join = FALSE, $groupBy = FALSE)
    {
        if (empty($where)) {
            return [];
        }
        $result = $this->getRows($where, $cols, $limit, $offset, $orderBy, $join, $groupBy);
        return $result;
    }


    /**
     * 获取标签信息
     */
    public function getTagsKoubei($where, $fields, $conditions)
    {
        if (empty($where)) {
            return [];
        }
        $limit = FALSE;
        $offset = 0;
        $orderBy = FALSE;
        $join = FALSE;
        $groupBy = FALSE;

        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
        }
        if (isset($conditions['offset'])) {
            $offset = $conditions['offset'];
        }
        if (isset($conditions['order_by'])) {
            $orderBy = $conditions['order_by'];
        }
        if (isset($conditions['join']) && $conditions['join'] == 'koubei') {
            $join = 'LEFT JOIN koubei ON koubei_tags_relation.koubei_id = koubei.id';
        }
        if (isset($conditions['group_by'])) {
            $groupBy = $conditions['group_by'];
        }
        $result = $this->getRows($where, $fields, $limit, $offset, $orderBy, $join, $groupBy);
        return $result;
    }
}