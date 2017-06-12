<?php
namespace mia\miagroup\Model\Ums;

use Ice;
use mia\miagroup\Data\Audit\UserShield as UserShieldData;

class User extends \DB_Query {
    protected $tableUsers = 'users';
    protected $tableUserSupplierMapping = 'user_supplier_mapping';
    protected $tableUserCategory = 'group_user_category';
    protected $tableUserPermission = 'group_user_permission';
    protected $tableLivePermission = 'group_live_room';
    protected $tableUserShield = 'user_shield';
    protected $tableGroupUserRole = 'group_user_role';

    /**
     * 获取所有自主口碑商家
     */
    public function getAllKoubeiSupplier() {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableUserSupplierMapping;
        $data = $this->getRows();
        $result = array();
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[$v['supplier_id']] = $v['user_id'];
            }
        }
        return $result;
    }
    
    /**
     *批量获取分类用户id
     * @return array() 推荐列表
     */
    public function getGroupUserIdList($type, $cate="",$page=0,$limit=false) {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableUserCategory;
        $result = array();
        $where = array();
    
        $where[] = ['status', 1];
        $where[] = ['type', $type];
        if(!empty($cate)){
            $where[] = ['category', $cate];
        }
        $orderBy = ['create_time DESC'];
    
        $userIdRes = $this->getRows($where, array('user_id'), $limit, $page, $orderBy);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $result['uids'] = $userIdArr;
        $count = $this->getRow($where, 'count(*) as nums');
        $result['count'] = $count;
        
        return $result;
    }
    
    /**
     *批量获取各类权限（视频、专栏等）用户id
     *@param $type 用户权限分类
     *@param $cateSelect 是否选择了用户分类(0没选；1选择了)
     *@param $category 用户分类
     *@param $subCate 用户子分类
     * @return array() 权限用户信息列表
     */
    public function getPermissionUserIdList($type,$page=0,$limit=false,$category="",$subCate="") {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableUserPermission;
        $userCateTable = $this->tableUserCategory;
        
        $result = array();
        $where = array();
        if(!empty($category)){
            $join = 'inner join '.$userCateTable. ' as c on c.user_id='. $this->tableName.'.user_id';
            $where[] = ['c.type', $category];
            $where[] = ['c.status', 1];
            if(!empty($subCate)){
                $where[] = ['c.category', $subCate];
            }
        }
        $where[] = [$this->tableName.'.status', 1];
        $where[] = [$this->tableName.'.type', $type];
        $orderBy = [$this->tableName.'.id DESC'];
    
        $userIdRes = $this->getRows($where, array($this->tableName.'.user_id as user_id'), $limit, $page, $orderBy, $join);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $result['uids'] = $userIdArr;
        $count = $this->getRow($where, 'count(1) as nums',false,$join);
        $result['count'] = $count;
    
        return $result;
    }
    
    /**
     * 获取屏蔽用户
     */
    public function getShieldUserIdList($userIds=array(),$page=0,$limit=false) {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableUserShield;
        $result = array();
        $where = array();
    
        if(!empty($userIds)){
            $where[] = ['user_id',$userIds];
        }
        $where[] = ['status', 1];
        $orderBy = ['create_time DESC'];
        $userIdRes = $this->getRows($where, array('user_id'), $limit, $page, $orderBy);
        //print_r($userIdRes);exit;
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $result['uids'] = $userIdArr;
        $count = $this->getRow($where, 'count(*) as nums');
        $result['count'] = $count;
        return $result;
    }
    
    /**
     *批量获取直播用户id
     * @return array() 直播用户信息列表
     */
    public function getLiveUserIdList($page=0,$limit=false,$category="",$subCate="") {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableLivePermission;
        $userCateTable = $this->tableUserCategory;
        
        $result = array();
        $where = array();
        if(!empty($category)){
            $join = 'inner join '.$userCateTable. ' as c on c.user_id='. $this->tableName.'.user_id';
            $where[] = ['c.type', $category];
            $where[] = ['c.status', 1];
            if(!empty($subCate)){
                $where[] = ['c.category', $subCate];
            }
        }
        
        if(!empty($userId)){
            $where[] = [$this->tableName.'.user_id',$userId];
        }
        $where[] = [$this->tableName.'.status', 1];
        $orderBy = [$this->tableName.'.create_time DESC'];
    
        $userIdRes = $this->getRows($where, array('distinct '. $this->tableName.'.user_id as user_id'), $limit, $page, $orderBy,$join);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['user_id'];
            }
        }
        $result['uids'] = $userIdArr;
        $count = $count = $this->getRow($where, 'count(1) as nums',false,$join);
        $result['count'] = $count;
    
        return $result;
    }
    
    /**
     * 获取用户id列表
     */
    public function getUserIdList($page=0,$limit=false) {
        $this->dbResource = 'miadefaultums';
        $this->tableName = $this->tableUsers;
        $result = array();
        $where = array();
    
        $orderBy = ['create_date DESC'];
        $userIdRes = $this->getRows($where, array('id'), $limit, $page, $orderBy);
        $userIdArr = array();
        if ($userIdRes) {
            foreach ($userIdRes as $value) {
                $userIdArr[] = $value['id'];
            }
        }
        $result['uids'] = $userIdArr;
        $count = $this->getRow($where, 'count(*) as nums');
        $result['count'] = $count;
        return $result;
    }
    
    /**
     * 根据username查询uid
     */
    public function getUidByUserName($userName) {
        $this->dbResource = 'miadefaultums';
        $this->tableName = $this->tableUsers;
        if (empty($userName)) {
            return false;
        }
        if (mb_strlen($userName, 'utf8') > 18) {
            $userName = mb_substr($userName, 0, 18, 'utf8');
            $where[] = array(':like_begin','username', $userName);
        } else {
            $where[] = array(':eq','username', $userName);
        }
        
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 根据nickname查询uid
     */
    public function getUidByNickName($nickName) {
        $this->dbResource = 'miadefaultums';
        $this->tableName = $this->tableUsers;
        if (empty($nickName)) {
            return false;
        }
        $where[] = array('nickname', $nickName);
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }
    
    /**
     * 根据手机号查询uid
     */
    public function getUidByPhone($phone) {
        $this->dbResource = 'miadefaultums';
        $this->tableName = $this->tableUsers;
        if (empty($phone)) {
            return false;
        }
        $where[] = array('cell_phone', $phone);
        $data = $this->getRow($where, 'id');
        if (!empty($data)) {
            return $data['id'];
        } else {
            return false;
        }
    }

    /*
     * 获取用户分组信息
     * */
    public function getGroupUserRole()
    {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableGroupUserRole;
        $where[] = ['status',1];
        $field = "role_id, role_name";
        $groupBy = ['role_id'];
        $res = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        return $res;
    }
    
}