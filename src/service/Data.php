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
        if(!isset($settings["start_time"]) || !isset($settings["end_time"]) || empty(trim($settings["start_time"])) || empty(trim($settings["end_time"]))) {
            return $this->error(500, '时间范围必须选择！');
        }

        $setData = [];
        $setData["start_time"] = $settings["start_time"];
        $setData["end_time"] = $settings["end_time"];
        
        if(isset($settings["all_label"]) && !empty(trim($settings["all_label"]))){
            $setData["all_label"] = explode(',', $settings["all_label"]);
            array_walk($setData["all_label"], function (&$n) {
                $n = intval($n);
            });
            
        }
        if(isset($settings["without_label"]) && !empty(trim($settings["without_label"]))){
            $setData["without_label"] = explode(',', $settings["without_label"]);
            array_walk($setData["without_label"], function (&$n) {
                $n = intval($n);
            });
        }
        if(isset($settings["one_label"]) && !empty(trim($settings["one_label"]))){
            $setData["one_label"] = explode(',', $settings["one_label"]);
            array_walk($setData["one_label"], function (&$n) {
                $n = intval($n);
            });
        }
        if(isset($settings["subject_source"]) && !empty(trim($settings["subject_source"]))){
            $setData["subject_source"] = explode(',', $settings["subject_source"]);
            array_walk($setData["subject_source"], function (&$n) {
                $n = intval($n);
            });
        }
        if(isset($settings["stat_cate"]) && !empty(trim($settings["stat_cate"]))){
            $setData["stat_cate"] = explode(',', $settings["stat_cate"]);
            array_walk($setData["stat_cate"], function (&$n) {
                $n = intval($n);
            });
        }
        
        if(isset($settings["role_id"]) && intval($settings["role_id"]) > 0){
            $setData["role_id"] = $settings["role_id"];
        }
        if(isset($settings["active_id"]) && intval($settings["active_id"]) > 0){
            $setData["active_id"] = $settings["active_id"];
        }
        if(isset($settings["subject_status"])){
            $setData["subject_status"] = intval($settings["subject_status"]);
        }
        if(isset($settings["need_title"])){
            $setData["need_title"] = intval($settings["need_title"]);
        }
        if(isset($settings["need_img_url"])){
            $setData["need_img_url"] = intval($settings["need_img_url"]);
        }
        if(isset($settings["need_item"])){
            $setData["need_item"] = intval($settings["need_item"]);
        }
        if(isset($settings["label_num"]) && intval($settings["label_num"]) > 0){
            $setData["label_num"] = $settings["label_num"];
        }
        
        $insertData = [];
        $insertData['create_time'] = date("Y-m-d H:i:s");
        $insertData['settings'] = json_encode($setData);
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