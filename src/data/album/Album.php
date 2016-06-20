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
    
    /**
     * 专辑列表
     * @params array() user_id 用户ID
     * @return array() 专辑列表
     */
    public function getAlbumList($params) {
        $result = array();
        $where = array();
        $where[] = array(':eq', 'user_id', $params['user_id']);
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $data = $this->getRows($where, array('id','user_id','title'), $limit, $offset, $orderBy);
        return $data;
    }
}
