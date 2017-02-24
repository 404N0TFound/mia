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
     * @return array
     */
    public function getBatchSubjects($conditions)
    {
        if (isset($conditions['name_md5'])) {
            $where[] = ['name_md5', $conditions['name_md5']];
        }
        if (isset($conditions['id'])) {
            $where[] = ['id', $conditions['id']];
        }
        if(empty($where)){
            return [];
        }
        $data = $this->getRows($where);
        return $data;
    }
}