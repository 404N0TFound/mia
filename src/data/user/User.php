<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class User extends DB_Query {

    protected $dbResource = 'miadefault';

    protected $tableName = 'users';
    protected $tableUserAddress = 'user_address';
    protected $tableProvince = 'province';
    protected $tableCity = 'province_city';
    protected $tableArea = 'province_city_area';
    protected $tableTown = 'province_city_area_town';


    protected $mapping = [];

    /**
     * 获取用户的基本信息
     *
     * @param mix $user_ids
     *            用户ids
     *@param bool $is_shield 是否屏蔽屏蔽用户
     * @return array
     */
    public function getUserInfoByIds($user_ids) {    
        $field = "id,username,nickname,child_birth_day,user_status,cell_phone,child_sex,child_nickname,consume_money,icon,level,is_id_verified,is_cell_verified,mibean_level,create_date,status,user_type";
        $where[] = array('id', $user_ids);
        $user_data = $this->getRows($where, $field);
        $result = array();
        if (!empty($user_data)) {
            foreach ($user_data as $k => $v) {
                //http转https
                $v['icon'] = str_replace('http://', 'https://', strval($v['icon']));
                $result[$v['id']] = $v;
            }
        }
        return $result;
    }
    
    /**
     * 新增用户
     */
    public function addUser($userInfo) {
        $data = $this->insert($userInfo);
        return $data;
    }
    
    /**
     * 根据userId更新用户信息
     */
    public function updateUserById($userId, $userInfo) {
        $where = array();
        $where[] = array('id', $userId);
        $data = $this->update($userInfo, $where);
        return $data;
    }
    
    /**
     * 根据username查询uid
     */
    public function getUidByUserName($userName) {
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

    /*
     * 获取蜜芽圈用户收货地址
     * */
    public function getGroupUserAddress($addressIds)
    {
        $where = $result = [];
        $where[] = ['ad.id', $addressIds];
        $where[] = ['ad.status', 1];

        $join = 'left join '.$this->tableUserAddress.' as ad on '.$this->tableName.'.id=ad.user_id ';
        $join .= 'left join '.$this->tableProvince. ' as p on ad.prov=p.id ';
        $join .= 'left join '.$this->tableCity. ' as c on ad.city=c.id ';
        $join .= 'left join '.$this->tableArea. ' as a on ad.area=a.id ';
        $join .= 'left join '.$this->tableTown. ' as t on ad.town=t.id ';

        $field = 'ad.phone as phone,ad.id as id,ad.cell as mobile,ad.prov as prov_id,ad.city as city_id,ad.area as area_id,ad.town as town_id,ad.user_id as user_id,ad.name as name,ad.address as address,p.name as prov,c.name as city,a.name as area,t.name as town';

        $data = $this->getRows($where, $field, FALSE, 0, FALSE, $join);

        if (empty($data)) {
            return [];
        }
        foreach ($data as $v) {
            $result[$v['id']] = $v;
        }
        return $result;
    }

}
