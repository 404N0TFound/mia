<?php
namespace mia\miagroup\Data\Subject;

class Tab extends \DB_Query
{
    protected $dbResource = 'miagroup';
    protected $tableName = 'group_tab';
    protected $mapping = [];

    /**
     * 获取标签信息
     * @param $conditions
     * @param array
     */
    public function getBatchSubjects($conditions)
    {
        if (isset($conditions['id'])) {
            $where[] = ['id', $conditions['id']];
        }
        $data = $this->getRows($where);
        return $data;
    }
}