<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class UserNews extends DB_Query
{
    public $dbResource = 'mianews';
    public $mapping = [];
    public $user_id = 0;
    public $table_num = 1024;
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
        if (empty($data)) {
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
        if (isset($conditions['news_type'])) {
            $where[] = ['news_type', $conditions['news_type']];
        }

        $fields = "id,create_time";
        $data = $this->getRows($where, $fields, $limit, 0, $order_by);

        if (!empty($data)) {
            $id_arr = [];
            foreach ($data as $val) {
                $id_arr[$val["id"]] = strtotime($val["create_time"]);
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

        if (isset($conditions['by_day'])) {
            $timeBegin = date("Y-m-d");
            $timeEnd = date("Y-m-d H:i:s", strtotime($timeBegin) + 86399);
            $where[] = [':gt', 'create_time', $timeBegin];
            $where[] = [':lt', 'create_time', $timeEnd];
        }

        if (isset($conditions['news_type'])) {
            $where[] = ['news_type', $conditions['news_type']];
        }
        if (isset($conditions['source_id'])) {
            $where[] = ['source_id', $conditions['source_id']];
        }
        if (isset($conditions['status'])) {
            $where[] = ['status', $conditions['status']];
        } else {
            $where[] = ['status', 1];
        }

        if (isset($conditions['limit'])) {
            $limit = $conditions['limit'];
        } else {
            $limit = false;
        }

        $order = "create_time desc";

        if (isset($conditions['fields'])) {
            $fields = $conditions['fields'];
        } else {
            $fields = "id,news_type,user_id,send_user,news_id,is_read,source_id,ext_info,create_time";
        }
        $data = $this->getRows($where, $fields, $limit, 0, $order);
        foreach ($data as &$val) {
            if (isset($val["ext_info"])) {
                $val["ext_info"] = json_decode($val["ext_info"], true);
            }
        }
        return $data;
    }


    /**
     * 批量设置已读状态
     * @param $userId
     * @return bool
     */
    public function changeReadStatus($userId, $type = [])
    {
        $isShardExists = $this->doShard(["user_id" => $userId]);
        if (!$isShardExists) {
            return false;
        }
        $setData[] = ['is_read', 1];
        $where[] = ['user_id', $userId];
        if(!empty($type)) {
            $where[] = ['news_type', $type];
        }
        $res = $this->update($setData, $where);
        return $res;
    }


    /**
     * 获取未读消息计数
     */
    public function getUserNewsNum($conditions)
    {
        $isShardExists = $this->doShard($conditions);
        if (!$isShardExists) {
            return 0;
        }
        $where = [];
        if (isset($conditions['user_id'])) {
            $where[] = ['user_id', $conditions['user_id']];
        }
        if (isset($conditions['news_type'])) {
            $where[] = ['news_type', $conditions['news_type']];
        }
        $where[] = ['status', 1];
        $where[] = ['is_read', 0];
        $fields = "count(id) as num";
        $data = $this->getRow($where, $fields);
        return intval($data["num"]);
    }


    /**
     * 更新
     * @param $setData
     * @param $where
     * @return mixed
     */
    public function updateNews($setData, $where)
    {
        $isShardExists = $this->doShard($setData);
        foreach ($setData as $key => $val) {
            $changeData[] = [$key, $val];
        }
        if (!$isShardExists) {
            return false;
        }
        $res = $this->update($changeData, $where);
        return $res;
    }

    /**
     * 更新状态
     * @param $where
     * @return mixed
     */
    public function updateStatus($where)
    {
        $isShardExists = $this->doShard($where);
        if (!$isShardExists) {
            return false;
        }
        foreach ($where as $key => $val) {
            $conditions[] = [$key, $val];
        }
        $setData[] = ["status", 0];
        $res = $this->update($setData, $conditions);
        return $res;
    }
}