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
        if($userIds){
            $where[] = ['user_id', $userIds];
        }
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
        $limit = 10;
        $offset = 0;
        $albumList = array();
        $where = array();
        if(isset($params['user_id']) && $params['user_id']){
            $where[] = array(':eq', 'user_id', $params['user_id']);
        }
        if (intval($params['iPageSize']) > 0) {
            $offset = ($params['page'] - 1) > 0 ? (($params['page'] - 1) * $params['iPageSize']) : 0;
            $limit = $params['iPageSize'];
        }
        $orderBy = array('create_time DESC');
        $limit = FALSE;
        $offset = 0;
        $data = $this->getRows($where, array('id','user_id','title'), $limit, $offset, $orderBy);
        if($data){
            foreach($data as $value){
                $albumList[$value['id']] = $value;
            }
        }
        return $albumList;
    }
    
    /**
     * 专辑信息
     * @params array() $albumIdArr 专辑ID
     * @return array() 专辑信息
     */
    public function getAlbumInfo($albumIdArr) {
        $where = array();
        $res = array();
        if(empty($albumIdArr)){
            return $res;
        }
        if($albumIdArr){
            $where[] = array(':in', 'id', $albumIdArr);
        }
        $data = $this->getRows($where, array('id','user_id','title'));
        if($data){
            foreach ($data as $value){
                $res[$value['id']] = $value;
            }
        }
        return $res;
    }
    
    /**
     * 更新专栏辑接口
     * @params array() user_id 用户ID
     * @set    array() title 标题
     * @return array() 专栏辑信息
     */
    public function updateAlbumFile($whereCon,$setData,$orderBy = FALSE, $limit = FALSE) {
        $where = array();
        if(isset($whereCon['id']) && $whereCon['id']){
            $where[] = array('id',$whereCon['id']);
        }
        if(isset($whereCon['user_id']) && $whereCon['user_id']){
            $where[] = array('user_id',$whereCon['user_id']);
        }
        $set = array();
        if(isset($setData['title']) && $setData['title']){
            $set[] = array('title',$setData['title']);
        }
        $data = $this->update($set, $where, $orderBy, $limit);
        return $data;
    }
    
    
    /**
     * 删除专栏辑接口(如果删除，该专栏辑下所有文章删除)
     * @params array() $userId 用户ID
     * @params array() $id   ID
     * @return array() true false
     */
    public function delAlbumFile($where){
        $data = $this->delete( $where);
        return $data;
    }
    
    /**
     * 插入专栏辑接口
     * @params array() title  title
     * @params array() user_id 用户ID
     * @return array() true false
     */
    public function addAlbumFile($insert){
        $data = array(
            'title'=>$insert['title'],
            'user_id'=>$insert['user_id'],
            'create_time'=>date("Y-m-d H:i:s")
        );
        return $this->insert($data);
    }
    
    /**
     * 根据用户id和专栏辑名称查询专栏辑
     */
    public function getAlbumFileByUidAndTitle($userId, $title) {
        if (empty($userId) || empty($title)) {
            return false;
        }
        $where[] = ['user_id', $userId];
        $where[] = ['title', $title];
        $albumInfo = $this->getRow($where);
        return $albumInfo;
    }
}
