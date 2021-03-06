<?php
namespace mia\miagroup\Data\Feed;

class Subject extends \DB_Query
{
    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subjects';


    public function getSubjectList($userIds,$offset=0,$limit=10,$source=array(),$filter=array())
    {
        if(!is_array($userIds)){
            return [];
        }

        $where[] = ['user_id',$userIds];
        $where[] = ['status',1];
        if (!empty($source)) {
            $where[] = ['source',$source];
        }
        if(isset($filter['title_status']) && $filter['title_status'] == 1){
            $where[] = [':!=','title',' '];
        }
        if(isset($filter['subject_id']) && $filter['subject_id'] > 0){
            $where[] = [':!=','id',$filter['subject_id']];
        }
        $data = $this->getRows($where, '*', $limit, $offset, 'created desc,id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value['id'];
        }
        return $result;

    }
}