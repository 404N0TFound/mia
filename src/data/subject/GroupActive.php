<?php
namespace mia\miagroup\Data\Subject;

class GroupActive extends \DB_Query{

    protected $tableName = 'group_active';
    protected $dbResource = "miagroup";
    protected $mapping = [];

    /*
     * 获取活动信息
     * */
    public function getGroupActive($month)
    {
        $time = date("Y-m-d", strtotime("-".$month." month"));
        $where[] = ['status',1];
        $field = "id, title";
        $where[] = [':gt', 'created', $time];
        $res = $this->getRows($where, $field);
        return $res;
    }
}
