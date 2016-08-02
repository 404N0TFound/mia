<?php
namespace mia\miagroup\Data\Push;
use \DB_Query;

class AppMsgLists extends DB_Query
{
    protected $dbResource = 'miagroup';

    protected $tableName = 'app_msg_lists';

    protected $mapping = array();

    /**
     * 添加推送消息
     *
     * @return void
     * @author 
     **/
    public function addMsg($msgData)
    {
        $data = $this->insert($msgData);
        return $data;
    }
}