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
        if(empty($where)){
            return [];
        }
        $result = $this->getRows($where, $cols, $limit, $offset, $orderBy, $join, $groupBy);
        return $result;
    }
}