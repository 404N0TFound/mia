<?php
namespace mia\miagroup\Data\Live;

use Ice;

class ChatHistory extends \DB_Query {

    protected $dbResource = 'log';

    protected $tableName = 'chat_history';

    protected $mapping = array();

    /**
     * 新增多条历史消息记录
     */
    public function addChatHistories($chatHistories) 
    {
        $data = $this->multiInsert($chatHistories);
        return $data;
    }

    /**
     * 根据msgUID获取历史消息记录
     */
    public function getChatHistoryByMsgUID($msgUID) {
        $where[] = ['msgUID', $msgUID];
        return $this->getRows($where);
    }


}