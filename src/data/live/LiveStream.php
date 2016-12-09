<?php
namespace mia\miagroup\Data\Live;

use Ice;

class LiveStream extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_live_stream';

    protected $mapping = array();

    /**
     * 添加直播流信息
     */
    public function addStreamInfo($streamInfo) {
        $data = $this->insert($streamInfo);
        return $data;
    }
}