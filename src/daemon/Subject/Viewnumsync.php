<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Model\Subject as SubjectModel;

/*
 * 定时任务：将阅读量同步到数据库
 */
class Viewnumsync extends \FD_Daemon{

    public function __construct() {
        $this->subjectModel = new SubjectModel();
        $this->subjectData = new SubjectData();
    }
    
    public function execute() {
        //从队列中读取阅读量计数
        //按subject_id sum
        //更新数据库计数
    }
}