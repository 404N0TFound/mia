<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Task extends \DB_Query {
    protected $tableDataTask = 'group_data_task';
    protected $tableDataResult = 'group_data_result';

    /**
     * 获取任务列表
     */
    public function getTaskList($page=0,$limit=false) {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableDataTask;
        $result = array();
        $where = array();
    
        $orderBy = ['id DESC'];
        $taskRes = $this->getRows($where, '*', $limit, $page, $orderBy);
        $taskArr = array();
        if (!empty($taskRes)) {
            foreach ($taskRes as $value) {
                $taskArr[$value['id']]['id'] = $value['id'];
                if(!empty($value['settings'])){
                    $settings = json_decode($value['settings'],true);
                }
                if(isset($settings['aggr_type'])){
                    $taskArr[$value['id']]['aggr_type'] = $settings['aggr_type'];
                }
                if(isset($settings['stat_cate'])){
                    $taskArr[$value['id']]['stat_cate'] = $settings['stat_cate'];
                }
                if(isset($settings['all_label'])){
                    $taskArr[$value['id']]['all_label'] = implode(',', $settings['all_label']);
                }
                if(isset($settings['without_label'])){
                    $taskArr[$value['id']]['without_label'] = implode(',', $settings['without_label']);
                }
                if(isset($settings['one_label'])){
                    $taskArr[$value['id']]['one_label'] = implode(',', $settings['one_label']);
                }
                if(isset($settings['role_id'])){
                    $taskArr[$value['id']]['role_id'] = $settings['role_id'];
                }
                if(isset($settings['active_id'])){
                    $taskArr[$value['id']]['active_id'] = $settings['active_id'];
                }
                if(isset($settings['subject_status'])){
                    $taskArr[$value['id']]['subject_status'] = $settings['subject_status'];
                }
                if(isset($settings['subject_source'])){
                    $taskArr[$value['id']]['subject_source'] = implode(',', $settings['subject_source']);
                }
                if(isset($settings['need_title'])){
                    $taskArr[$value['id']]['need_title'] = $settings['need_title'];
                }
                if(isset($settings['need_img_url'])){
                    $taskArr[$value['id']]['need_img_url'] = $settings['need_img_url'];
                }
                if(isset($settings['need_item'])){
                    $taskArr[$value['id']]['need_item'] = $settings['need_item'];
                }
                if(isset($settings['label_num'])){
                    $taskArr[$value['id']]['label_num'] = $settings['label_num'];
                }
                if(isset($settings['start_time'])){
                    $taskArr[$value['id']]['start_time'] = $settings['start_time'];
                }
                if(isset($settings['end_time'])){
                    $taskArr[$value['id']]['end_time'] = $settings['end_time'];
                }
            }
        }
        $result['list'] = $taskArr;
        $count = $this->getRow($where, 'count(*) as nums');
        $result['count'] = $count;
        return $result;
    }
    
    /**
     * 获取任务数据列表
     */
    public function getTaskDataList($taskIds) {
        $this->dbResource = 'miagroupums';
        $this->tableName = $this->tableDataResult;
        $result = array();
        $where = array();
    
        $where[] = ['task_id',$taskIds];
        $taskDataRes = $this->getRows($where, '*');
        $taskDataArr = array();
        if (!empty($taskDataRes)) {
            foreach ($taskDataRes as $value) {
                $taskDataArr[$value['task_id']][[$value['id']]]['id'] = $value['id'];
                if(!empty($value['result'])){
                    $taskResult = json_decode($value['result'],true);
                    $taskDataArr[$value['task_id']][$value['id']]['result'] = $taskResult;
                }
            }
        }
        return $taskDataArr;
    }
    
    
    
}