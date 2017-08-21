<?php
namespace mia\miagroup\Daemon\Subject;
use mia\miagroup\Service\Subject as SubjectService;
use \mia\miagroup\Remote\Solr;
/*
 * 定时任务：初始化素材数据，
 * 将符合条件的蜜芽圈和口碑贴设置为素材类型（更新到帖子表的扩展字段中）
 */
class InitMaterialSubject extends \FD_Daemon{
    private $subjectService;
    private $lastIdFile;
    
    public function __construct(){
        date_default_timezone_set("Asia/Shanghai");
        set_time_limit(3600);
        ini_set('memory_limit','128M');
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/subject/';
        $this->lastIdFile = $tempFilePath . 'initmaterial_last_id';
        //$this->lastIdFile = "D:/htdocs/repos/groupservice/var/daemonlogs/initmaterial_last_id";
        $this->subjectService = new SubjectService();
    }
    
    public function execute() {
        //读取上一次处理的id
        if (!file_exists($this->lastIdFile)) { //打开文件
            $lastId = 0;
            $fpLastIdFile = fopen($this->lastIdFile, 'w');
        } else {
            $fpLastIdFile = fopen($this->lastIdFile, 'r+');
        }
        
        
        if (!flock($fpLastIdFile, LOCK_EX | LOCK_NB)) { //加锁
            fclose($fpLastIdFile);
            return;
        }
        if (!isset($lastId)) { //获取last_id
            $lastId .= fread($fpLastIdFile, 1024);
            $lastId = intval($lastId);
        }
        //拉取新的口碑
        $where = [];
        $where['after_id'] = $lastId;
        $where['status'] = 2;

        $solr = new Solr('koubei');
        $solrData = $solr->getKoubeiList($where, array(), 1, 1000,'id asc')['list'];
        foreach ($solrData as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            //收集口碑表中帖子id
            //1、帖子id大于0且口碑为精品
            //2、蜜芽贴同步过来的（帖子id大于0且是蜜芽贴同步过来的，特征为score为0且蜜芽贴为精品）
            if($value['subject_id'] > 0 && ($value['rank'] == 1 || ($value['score'] == 0 && $value['is_fine'] == 1))){
                //更新帖子表中扩展字段（扩展字段中新增素材标识）
                $setData = [];
                $setData['ext_info']['is_material'] = 1;
                $this->subjectService->updateSubject($value['subject_id'],$setData);
            }else{
                continue;
            }
        }
        
        //写入本次处理的最大event_id
        if (isset($maxId)) {
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
        }
        
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }
    
}