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
        //任务状态：0:已删除;1:未执行;2:执行中;3:已完成;4:执行失败;
        $statusConf = array(0=>'已删除',1=>'未执行',2=>'执行中',3=>'已完成',4=>'执行失败');
        
        foreach ($taskRes['list'] as $taskv){
            $taskDataRes[$taskv['id']] = $taskv;
            $taskDataRes[$taskv['id']]['c_status'] = $statusConf[$taskv['status']];
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