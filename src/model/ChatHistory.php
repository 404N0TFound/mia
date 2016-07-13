<?php
namespace mia\miagroup\Model;
use \F_Ice;
use mia\miagroup\Data\Live\ChatHistory as ChatHistoryData;

class ChatHistory {
    
    public $chatHistoryData;
    
    public function __construct()
    {
        $this->chatHistoryData = new ChatHistoryData();
    }
    

    /**
     * 新增消息历史记录
     */
    public function addChatHistories($chatHistories)
    {
        $data = $this->chatHistoryData->addChatHistories($chatHistories);
        return $data;
    }
    
    /**
     * 根据msgUID获取历史消息记录
     */
    public function getChatHistoryByMsgUID($msgUID)
    {
        $data = $this->chatHistoryData->getChatHistoryByMsgUID($msgUID);
        return $data;
    }
    
}