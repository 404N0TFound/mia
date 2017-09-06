<?php

namespace mia\miagroup\Data\News;

use \DB_Query;

class SystemNews extends DB_Query
{
    public $dbResource = 'mianews';
    public $tableName = 'system_news';
    public $mapping = [];


    /**
     * 添加系统消息
     * @param $insertData
     * @return mixed
     */
    public function addSystemNews($insertData)
    {
        $res = $this->insert($insertData);
        return $res;
    }


    /**
     * 查询系统消息列表
     * @param $conditions
     * @return array
     */
    public function getSystemNewsList($conditions)
    {
        if (empty($conditions)) {
            return [];
        }

        if (isset($conditions['id'])) {
            $where[] = ['id', $conditions['id']];
        }
        if (isset($conditions['status'])) {
            $where[] = ['status', $conditions['status']];
        }
        if (isset($conditions["gt"]['id'])) {
            //大于最大系统消息ID
            $where[] = [':gt', 'id', $conditions["gt"]['id']];
        }
        if (isset($conditions["gt"]['create_time'])) {
            //大于消息创建时间
            $where[] = [':gt', 'create_time', $conditions["gt"]['create_time']];
        }
        if (isset($conditions["lt"]['create_time'])) {
            //大于消息创建时间
            $where[] = [':lt', 'create_time', $conditions["lt"]['create_time']];
        }
        if (isset($conditions["lt"]['send_time'])) {
            //发送时间小于当前时间
            $where[] = [':lt', 'send_time', $conditions["lt"]['send_time']];
        }
        if (isset($conditions["gt"]['send_time'])) {
            $where[] = [':gt', 'send_time', $conditions["gt"]['send_time']];
        }

        if (isset($conditions["gt"]['abandon_time'])) {
            //过期时间大于当前时间
            $where[] = [':gt', 'abandon_time', $conditions["gt"]['abandon_time']];
        }
        if (isset($conditions['send_type'])) {
            $where[] = ['send_type', $conditions['send_type']];
        }

        if (isset($conditions['news_type'])) {
            $where[] = ['news_type', $conditions['news_type']];
        }
        if (isset($conditions['content'])) {
            $where[] = [':like_literal', 'ext_info', "%" . $conditions['content'] . "%"];
        }
        if (isset($conditions['offset'])) {
            $offset = $conditions['offset'];
        } else {
            $offset = 0;
        }
        if (isset($conditions['pagesize'])) {
            $limit = $conditions['pagesize'];
        } else {
            $limit = FALSE;
        }

        if (isset($conditions['orderBy'])) {
            $orderBy = $conditions['orderBy'];
        } else {
            $orderBy = FALSE;
        }
        //查询字段
        $fields = 'id,news_type,send_user,send_time,ext_info,status,create_time';
        $data = $this->getRows($where, $fields, $limit, $offset,$orderBy);
        return $data;
    }

    /**
     * 更新系统消息表
     * @param $setData
     * @param $where
     * @return bool
     */
    public function updateSystemNews($setData, $where)
    {
        if (empty($setData) || empty($where) || !is_array($setData) || !is_array($where)) {
            return FALSE;
        }
        foreach ($setData as $key => $val) {
            $setData[] = [$key, $val];
        }
        foreach ($where as $k => $v) {
            $where[] = [$k, $v];
        }
        $res = $this->update($setData, $where);
        return $res;
    }
}