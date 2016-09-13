<?php
namespace mia\miagroup\Data\Feed;

class Subject extends \DB_Query
{
    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subjects';


    public function getSubjectList($userIds,$offset=0,$limit=10)
    {
        if(!is_array($userIds)){
            return [];
        }

        $where[] = ['user_id',$userIds];
        $where[] = ['status',1];
        $data = $this->getRows($where, '*', $limit, $offset, 'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value['id'];
        }
        return $result;

    }
}