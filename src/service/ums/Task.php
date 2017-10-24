<?php
namespace mia\miagroup\Service\Ums;

use \F_Ice;
use mia\miagroup\Lib\Service;
use mia\miagroup\Model\Ums\Task as TaskModel;
use mia\miagroup\Service\User as UserService;
use mia\miagroup\Service\Ums\User as UserUmsService;
use mia\miagroup\Service\Label as LabelService;

class Task extends Service{
    
    private $taskModel;
    
    public function __construct(){
        parent::__construct();
        $this->taskModel = new TaskModel();
    }
    
    
    /**
     * 获取任务及任务数据列表
     */
    public function getTaskList($page,$limit){
        //1、获取任务列表
        $taskRes = $this->taskModel->getTaskList($page,$limit);
        if(empty($taskRes['list'])){
            return $this->succ(array());
        }
        
        $taskDataRes = array();
        //4、将3中获取的信息拼接到1中，统一输出任务信息和任务执行结果信息
        foreach ($taskRes['list'] as $taskv){
            $taskDataRes[$taskv['id']] = $taskv;
            $tempData = array();
            if($taskv['aggr_type'] == 'user' && $taskv['stat_cate'] == 1){
                $tempData['uid'] = '用户id';
                $tempData['username'] = '用户名';
                $tempData['nickname'] = '用户昵称';
                $tempData['public'] = '发帖量';
                $tempData['fine'] = '加精量';
                $tempData['first'] = '首次发帖';
                $tempData['fans'] = '粉丝数';
                $tempData['time'] = '注册时间';
            }
            if($taskv['aggr_type'] == 'user' && $taskv['stat_cate'] == 2){
                $tempData['uid'] = '用户id';
                $tempData['username'] = '用户名';
                $tempData['nickname'] = '用户昵称';
                $tempData['phone'] = '电话';
                $tempData['receiver'] = '收件人';
                $tempData['public'] = '发帖量';
                $tempData['first'] = '首次发帖';
                $tempData['fine'] = '加精量';
                $tempData['address'] = '收货地址';
            }
            if($taskv['aggr_type'] == 'subject'){
                $tempData['uid'] = '用户id';
                $tempData['username'] = '用户名';
                $tempData['nickname'] = '用户昵称';
                $tempData['pid'] = '帖子id';
                $tempData['praise'] = '赞数';
            }
            if($taskv['aggr_type'] == 'user_label'){
                $tempData['uid'] = '用户id';
                $tempData['username'] = '用户名';
                $tempData['nickname'] = '用户昵称';
                $tempData['label_id'] = '标签id';
                $tempData['label_name'] = '标签名';
                $tempData['public'] = '发帖量';
                $tempData['first'] = '首次发帖';
                $tempData['fine'] = '加精量';
            }
            $taskDataRes[$taskv['id']]['task_data'] = $tempData;
        }
        $result['list'] = $taskDataRes;
        $result['count'] = $taskRes['count'];
        return $this->succ($result);
    }
    
    /**
     * 获取任务及任务数据列表
     */
    public function getTaskDataDetail($taskId){
        $userService = new UserService();
        $userUmsService = new UserUmsService();
        $labelService = new LabelService();
        
        $taskData = $this->taskModel->getTaskDataList(array($taskId))[$taskId];
        $taskDataRes = array();
        //4、将3中获取的信息拼接到1中，统一输出任务信息和任务执行结果信息
        foreach ($taskData as $key=>$taskv){
            $taskDataRes[$key] = $taskv;
            if(isset($taskv['result']['user_id']) && !empty($taskv['result']['user_id'])){
                //根据用户id获取用户信息
                $taskDataRes[$key]['user'] = $userService->getUserInfoByUids(array($taskv['result']['user_id']),0,array('cell_phone','count','ums'))['data'][$taskv['result']['user_id']];
                //根据用户id获取收货信息
                $taskDataRes[$key]['user_address'] = $userUmsService->getBatchUserAddress(array($taskv['result']['user_id']))['data'][$taskv['result']['user_id']];
            }
    
            if(isset($taskv['result']['label_ids']) && !empty($taskv['result']['label_ids'])){
                $taskDataRes[$key]['label'] = $labelService->getBatchLabelInfos(array($taskv['result']['label_ids']))['data'][$taskv['result']['label_ids']];
            }
        }
        return $this->succ($taskDataRes);
    }
    
}