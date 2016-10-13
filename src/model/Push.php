<?php
namespace mia\miagroup\Model;

use mia\miagroup\Data\Push\Appmsglists as MsgListsData;

class Push
{
    public $deviceTokenData = '';
    public $msgListsData    = '';

    public function __construct()
    {
        $this->msgListsData    = new MsgListsData();
    }

    /**
     * 添加推送消息
     *
     * @return array
     * @author 
     **/
    public function addMsg($msgData)
    {
        $data = $this->msgListsData->addMsg($msgData);
        return $data;
    }


}