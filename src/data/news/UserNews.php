<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class UserNews extends DB_Query
{
    public $dbResource = 'mianews';
    public $mapping = [];
    public $user_id = 0;
    public $table_num = 3;
    public $tableName = "";
    private $shard_field = 'user_id';//分表字段

    /*
     * 添加用户消息
     */
    public function addUserNews($insertData)
    {
        //分表分表操作
        $isShardExists = $this->doShard($insertData);
        if (!$isShardExists) {
            return false;
        }
        if (empty($this->tableName)) {
            return false;
        }
        $res = $this->insert($insertData);
        return $res;
    }

    /**
     * 分表操作
     */
    private function doShard($data)
    {
        if (array_key_exists($this->shard_field, $data)) {
            $this->user_id = $data["user_id"];
            $this->tableName = "user_news_" . (intval($this->user_id % $this->table_num) + 1);
            return true;
        }
        return false;
    }

    /**
     * 获取用户已经收到的最大系统消息ID
     * @param $conditions
     * @return bool|int
     */
    public function getMaxSystemId($conditions)
    {
        $isShardExists = $this->doShard($conditions);
        if (!$isShardExists) {
            return false;
        }
        if (empty($this->tableName)) {
            return false;
        }

        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        $where[] = ['status', 1];

        if (isset($conditions["gt"]['news_id'])) {
            //new_id大于0的是系统消息
            $where[] = [':gt', 'news_id', $conditions["gt"]['news_id']];
        }
        //查询字段
        $fields = 'news_id';
        $data = $this->getRows($where, $fields, 1, 0, "news_id DESC");
        if(empty($data)) {
            //新用户
            return 0;
        }
        return intval($data[0]["news_id"]);
    }

    /**
     * 获取用户消息列表ID
     */
    public function getUserNewIdList($conditions)
    {
        $isShardExists = $this->doShard($conditions);
        if (!$isShardExists) {
            return [];
        }
        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
        } else {
            $limit = 1000;
        }
        if (isset($conditions['order_by'])) {
            $order_by = $conditions['order_by'];
        } else {
            $order_by = "create_time DESC";
        }
        $fields = "id";
        $data = $this->getRows($where, $fields, $limit, 0, $order_by);

        if (!empty($data)) {
            $id_arr = [];
            foreach ($data as $val) {
                $id_arr[] = $val["id"];
            }
            return $id_arr;
        } else {
            return [];
        }
    }



    /**
     * 获取用户消息列表ID
     */
    public function getNewsList($conditions)
    {
        $isShardExists = $this->doShard($conditions);
        if (!$isShardExists) {
            return [];
        }
        $where[] = ['user_id', $conditions['user_id']];
        if (isset($conditions['id'])) {
            $where[] = ['id', $conditions['id']];
        }
        $where[] = ['status', 1];

        $fields = "id,news_type,user_id,send_user,is_read,source_id,ext_info";
        $data = $this->getRows($where, $fields);
        return $data;
    }

}