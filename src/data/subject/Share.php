<?php
namespace mia\miagroup\Data\Subject;

class Share extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_subject_share';

    protected $mapping = array();
    
    /**
     * 记录分享信息
     * @param unknown $sourceId
     * @param unknown $type
     * @param unknown $userId
     * @param unknown $platform
     * @param unknown $status
     */
    public function addShare($sourceId, $userId, $type, $platform,$status){
        $setInfo = array(
            "source_id" => $sourceId,
            "type"    => $type,
            "user_id"    => $userId,
            "platform"    => $platform,
            "status"    => $status,
            "create_time"    => date("Y-m-d H:i:s", time()),
        );
        $shareId = $this->insert($setInfo);
        return $shareId;
    }
    
    
}
