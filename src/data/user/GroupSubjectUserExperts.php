<?php
namespace mia\miagroup\Data\User;

use \DB_Query;

/**
 * 蜜芽圈专家表
 *
 * @author user
 */
class GroupSubjectUserExperts extends DB_Query {

    protected $tableName = 'group_subject_user_experts';

    protected $dbResource = 'miagroup';

    protected $mapping = array(
        'user_id' => 'i', 
        'desc' => 's', 
        'label' => 's', 
        'status' => 'i', 
        'last_modify' => 's', 
        'modify_author' => 'i', 
        'answer_nums' => 'i'
    );
    
    /**
     * 新增专家
     */
    public function addExpert($expertInfo) {
        $data = $this->insert($expertInfo);
        return $data;
    }
    
    /**
     * 批量获取专家信息
     */ 
    public function getBatchExpertInfoByUids($userIds) {
        $result = array();
        
        $where[] = ['status', 1];
        $where[] = ['user_id', $userIds];
        
        $experts = $this->getRows($where);

        if (!empty($experts)) {
            foreach ($experts as $expert) {
                $result[$expert['user_id']] = $expert;
            }
        }
        return $result;
    }
    
    /**
     * 修改专家信息
     */
    public function updateExpertInfoByUid($userId, $setData) {
        if (empty($userId)) {
            return false;
        }
        $where = array();
        $where[] = array('user_id', $userId);
        $data = $this->update($setData, $where);
        return $data;
    }
}
