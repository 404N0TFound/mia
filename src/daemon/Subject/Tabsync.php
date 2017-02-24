<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Lib\Redis;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Data\Subject\Tab as TabData;


/**
 * 定时更新蜜芽圈首页tab分类
 * Class Tabsync
 * @package mia\miagroup\Daemon\Subject
 */
class Tabsync extends \FD_Daemon
{
    public function __construct()
    {
        $this->subjectService = new SubjectService();
        $this->tabData = new TabData();
    }

    public function execute()
    {
        //取redis数据
        $redis = new Redis('recommend/default');
        //这时候的刷新操作有问题
        $allCate = $redis->get(\F_Ice::$ins->workApp->config->get('busconf.subject.recommendCateKey'));
        $cateArr = explode(' ', $allCate);
        foreach ($cateArr as $k => $v) {
            $tabInfos = $this->subjectService->getBatchTabInfos([$v]);
            $date = date('Y-m-d H:i:s');
            $name_md5 = md5($v);
            if (empty($tabInfos)) {
                //插入
                $sql = "INSERT INTO group_tab (tab_name,name_md5,update_time,create_time) VALUES ('{$v}','{$name_md5}','{$date}','{$date}')";
            } else {
                //更新
                $sql = "UPDATE group_tab SET update_time = '{$date}'  WHERE  name_md5 = '{$name_md5}'";
            }
            $this->tabData->query($sql);
        }
    }
}