<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

class PlusUser extends DB_Query
{

    protected $dbResource = 'miaplus';

    protected $tableName = 'plus_user_extension';

    protected $mapping = [];


    /*
     * 获取plus用户信息
     * */
    public function getPlusUserInfo($userIds) {

        $field = "user_id,identity_type,plus_status,weixin_code,weixin_nickname,weixin_icon";
        $where[] = array('user_id', $userIds);
        $where[] = array('plus_status', 1);
        $user_data = $this->getRows($where, $field);
        if (!empty($user_data)) {
            foreach ($user_data as $k => $v) {
                //http转https
                if(stripos($v['weixin_icon'],'https://') === false) {
                    $user_data[$k]['icon'] = str_replace('http://', 'https://', strval($v['icon']));
                }
            }
        }
        return $user_data;
    }

}