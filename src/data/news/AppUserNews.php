<?php
namespace mia\miagroup\Data\News;

use \DB_Query;

class AppUserNews extends DB_Query{

    public $dbResource = 'mianews';
    public $tableName = 'app_user_news';
    public $mapping = [];

    /**
     * 添加消息用户关系数据
     */
    public function addNewsUserRelation($newId,$toUserId) {
        $userNewsInfo = array("news_id" => $newId, "user_id" => $toUserId, "created" => date("Y-m-d H:i:s", time()));
        
        $data = $this->insert($userNewsInfo);
        return $data;
    }
    

}