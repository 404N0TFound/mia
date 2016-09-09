<?php
namespace mia\miagroup\Data\Comment;

class HeadLineChannelContent extends \DB_Query {

    protected $dbResource = 'miagroup';

    protected $tableName = 'group_headline_channel_content';

    protected $mapping = array();

    /**
     * 根据头条栏目获取头条
     */
    public function getHeadLinesByChannel($channelId, $page = 1) {
        
    }
}