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
    public function getChatHistoryByMsgUID($msgUID)
    {
        $where[] = ['msgUID', $msgUID];
        return $this->getRows($where, 'id');
    }

    /**
     * 历史消息列表
     *
     */
    public function getChathistoryList($cond, $offset = 0, $limit = 100 ,$orderBy='')
    {
        if (empty($cond['GroupId']) && empty($cond['msgUID']) && empty($cond['userId']) && empty($cond['contentType']))
            return false;
        $where = [];
        foreach ($cond as $k => $v) {
            $where[] = $v;
        }
        $data = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $data;
    }


}