<?php
namespace mia\miagroup\Data\Album;

use \DB_Query;

class Album extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_album';

    protected $mapping = array();

    /**
     * 查用户下专栏数
     * @params array() $userIds 用户ID
     * @return array() 用户下专栏数
     */
    public function getAlbumNum($userIds) {
        $numArr = array();
        $where = array();
        $where[] = ['user_id', $userIds];
        $field = 'user_id,count(*) as nums';
        $groupBy = 'user_id';
        $albumInfos = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        
        if($albumInfos){
            foreach ($albumInfos as $values) {
                $numArr[$values['user_id']] = $values['nums'];
            }
        }
        return $numArr;
    }
}
