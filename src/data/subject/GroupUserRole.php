<?php
namespace mia\miagroup\Data\Subject;

class GroupUserRole extends \DB_Query{

    protected $tableName = 'group_user_role';
    protected $dbResource = "miagroup";
    protected $mapping = [];

    /*
     * 获取用户分组信息
     * */
    public function getGroupUserRole()
    {
        $where[] = ['status',1];
        $field = "role_id, role_name";
        $groupBy = ['role_id'];
        $res = $this->getRows($where, $field, FALSE, 0, FALSE, FALSE, $groupBy);
        return $res;
    }
}
