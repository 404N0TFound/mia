<?php

namespace mia\miagroup\Service;
use mia\miagroup\Model\Data as DataModel;


class Data extends \mia\miagroup\Lib\Service
{
    public function __construct() {
        parent::__construct();
        $this->dataModel = new DataModel();
    }


    /**
     * 添加任务
     * @param $settings
     *
     * =====筛选条件=====
     * aggr_type 筛选维度 string | subject user user_label
     * all_label 必须包含的所有标签 array
     * without_label 不能包含的标签 array
     * without_label 只需包含其中一个的标签 array
     * role_id
     * active_id
     * subject_source 帖子来源  array
     * subject_status
     * need_title
     * need_img_url
     * need_item
     * label_num 标签数量
     * start_time
     * end_time
     * =====展示条件=====
     *
     */
    public function addDataTask($settings)
    {
        if(!isset($settings["start_time"]) || !isset($settings["start_time"])) {
            return $this->error(500, '时间范围必须选择！');
        }

        if(!empty($settings["all_label"])){
            $settings["all_label"] = explode(',', $settings["all_label"]);
            array_walk($settings["all_label"], function (&$n) {
                $n = intval($n);
            });
            
        }
        if(!empty($settings["without_label"])){
            $settings["without_label"] = explode(',', $settings["without_label"]);
            array_walk($settings["without_label"], function (&$n) {
                $n = intval($n);
            });
        }
        if(!empty($settings["one_label"])){
            $settings["one_label"] = explode(',', $settings["one_label"]);
            array_walk($settings["one_label"], function (&$n) {
                $n = intval($n);
            });
        }
        if(!empty($settings["subject_source"])){
            $settings["subject_source"] = explode(',', $settings["subject_source"]);
            array_walk($settings["subject_source"], function (&$n) {
                $n = intval($n);
            });
        }
        if(!empty($settings["stat_cate"])){
            $settings["stat_cate"] = explode(',', $settings["stat_cate"]);
            array_walk($settings["stat_cate"], function (&$n) {
                $n = intval($n);
            });
        }
        $insertData = [];
        $insertData['create_time'] = date("Y-m-d H:i:s");
        $insertData['settings'] = json_encode($settings);
        $res = $this->dataModel->addDataTask($insertData);
        if(!$res) {
            return $this->error(500, '添加失败！');
        }
        return $this->succ($res);
    }

    /**
     * 获取任务详情
     * @param $taskId
     */
    public function getDataTask($taskId)
    {

    }

    /**
     * 获取任务列表
     * @param $params
     * page
     * count
     */
    public function getTaskList($params)
    {
        $conditions = [];
        $conditions["page"] = 1;
        $conditions["count"] = 10;
        if (isset($params["page"]) && intval($params["page"]) > 0) {
            $conditions["page"] = intval($params["page"]);
        }
        if (isset($params["count"]) && intval($params["count"]) > 0) {
            $conditions["count"] = intval($params["count"]);
        }

        $res = $this->dataModel->getTaskList($conditions);
        return $this->succ($res);
    }

    /**
     * 获取任务数据
     */
    public function getTaskData($taskId, $page = 1, $count = 100)
    {
        if (empty($taskId)) {
            return $this->succ([]);
        }
        $res = $this->dataModel->getTaskData($taskId, $page, $count);
        return $this->succ($res);
    }

}