<?php
namespace mia\miagroup\Data\Label;

class UserLabelRelation extends \DB_Query
{
    protected $dbResource = 'miagroup';

    protected $tableName = 'group_label_user_relation';

    protected $mapping = array();

    /**
     * 查询用户和标签的关注关系
     */
    public function getLableRelationByUserId($userId, $labelId) {
        if(empty($userId) || empty($labelId)){
            return [];
        }
        $where[] = ['user_id', $userId];
        $where[] = ['label_id', $labelId];
        $relation = $this->getRow($where);
        return $relation;
    }

    /**
     * 更新关注状态
     */
    public function updateUserLabelRelate($id, $setData)
    {
        $data = array();
        if (isset($setData['status'])) {
            $data[] = array("status", intval($setData['status']));
        }
        if (!empty($setData['create_time'])) {
            $data[] = array("create_time", $setData['create_time']);
        }
        if (!empty($setData['update_time'])) {
            $data[] = array("update_time", $setData['update_time']);
        }
        $where[] = ['id',$id];
        $setRelationStatus = $this->update($data, $where);
        return $setRelationStatus;
    }

    /**
     * 插入关注标签记录
     */
    public function addUserLabelRelate($setData)
    {
        return $this->insert($setData);
    }

    /**
     * 根据userId获取标签
     */
    public function getLabelListByUid($userId,$offset=0,$limit=10)
    {
        if(empty($userId)){
            return [];
        }
        $where[] = ['user_id',$userId];
        $where[] = ['status',1];

        $data = $this->getRows($where,'label_id',$limit,$offset,'id desc');
        $result = [];
        foreach ($data as $key => $value) {
            $result[] = $value['label_id'];
        }
        return $result;
    }
}