<?php
namespace mia\miagroup\Data\Album;

use \DB_Query;

class AlbumPermission extends \DB_Query {

    protected $dbResource = 'miagroup';
    protected $tableName = 'group_subject_album_permission';
    protected $mapping = array();

    /**
     * 查用户编辑文章权限
     * @params array() $user_id 用户ID
     * @return array() id
     */
    public function getAlbumPermissionByUserId($user_id) {
        $where = array();
        $where[] = array(':eq', 'status', '1');
        $where[] = array('user_id', $user_id);
        if (is_array($user_id)) {
            $result = array();
            $data = $this->getRows($where, 'user_id, id');
            if (!empty($data)) {
                foreach ($data as $v) {
                    $result[$v['user_id']] = $v;
                }
            }
        } else {
            $result = $this->getRow($where, 'id');
        }
        return $result;
    }

    /**
     * 添加用户专栏权限
     */
    public function addAlbumPermission($userId, $source = 'ums', $reason = '', $operator = 0) {
        $insertData['user_id'] = $userId;
        $insertData['source'] = $source;
        if (!empty($reason)) {
            $insertData['reason'] = $reason;
        }
        if (intval($operator) > 0) {
            $insertData['operator'] = $operator;
        }
        $data = $this->insert($insertData);
        return $data;
    }
}
