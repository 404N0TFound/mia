<?php
namespace mia\miagroup\Daemon\Temp;
use mia\miagroup\Service\Subject as SubjectService;
use mia\miagroup\Data\Subject\Subject as SubjectData;
use mia\miagroup\Data\Koubei\Koubei as KoubeiData;

/**
  * 临时任务：将封测报告类的帖子取消素材标识
  * @author jiadonghui@mia.com
  *
  */
class Cancelsubjectmaterial extends \FD_Daemon{
    private $subjectService;
    private $subjectData;
    private $koubeiData;
    private $lastIdFile;
    private $allIdFile;
    
    public function __construct(){
        date_default_timezone_set("Asia/Shanghai");
        set_time_limit(3600);
        ini_set('memory_limit','128M');
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/subject/';
        $this->lastIdFile = $tempFilePath . 'cancel_initmaterial_last_id';
        $this->allIdFile = $tempFilePath . 'cancel_initmaterial_all_id';
//         $this->lastIdFile = "D:/htdocs/repos/groupservice/var/daemonlogs/initmaterial_last_id";
//         $this->allIdFile = "D:/htdocs/repos/groupservice/var/daemonlogs/initmaterial_all_id";
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

        //获取封测报告的口碑
        $this->koubeiData = new KoubeiData();
        $sql = "select id, subject_id,item_id,rank,score from koubei where id > " . $lastId. " and status=2 and type=1 and subject_id>0 and rank=0 order by id asc limit 5000";
        $koubeiRes  = $this->koubeiData->query($sql);
        
        if(!empty($koubeiRes)){
            foreach ($koubeiRes as $value) {
                if (isset($maxId)) { //获取最大event_id
                    $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
                } else {
                    $maxId = $value['id'];
                }
                //帖子id为0的情况，直接过滤掉
                if($value['subject_id'] <= 0){
                    continue;
                }
                //收集口碑表中帖子id
                //1、帖子id大于0且口碑为精品
                //2、蜜芽贴同步过来的（帖子id大于0且是蜜芽贴同步过来的，特征为score为0且蜜芽贴为精品）
                //3、帖子同时满足帖子文字数量大于等于20且图片数量大于等于3张
                if($value['score'] == 0){
                    //获取帖子的文字和图片信息
                    $this->subjectData = new SubjectData();
                    $sql = "select id,ext_info from group_subjects where id = " . $value['subject_id'] . " and status = 1";
                    $newSubject = $this->subjectData->query($sql);
                    $newSubject = $newSubject[0];
                    //如果该帖子不存在过滤掉
                    if(empty($newSubject)){
                        continue;
                    }
                    //获取帖子的扩展字段
                    if(empty($newSubject['ext_info'])){
                        continue;
                    }
                    $extInfo = json_decode($newSubject['ext_info'],true);
                    if(!isset($extInfo['is_material']) || $extInfo['is_material'] != 1){
                        continue;
                    }
                    //更新帖子表中扩展字段（扩展字段中封测报告帖子有素材标识的置为取消素材状态）
                    $setData = [];
                    $setData['ext_info']['is_material'] = 0;
                    $this->subjectService->updateSubject($value['subject_id'],$setData);
            
                    //更新后将帖子id和商品id保存起来，以供统计帖子和商品数量
                    file_put_contents($this->allIdFile, $value['subject_id']."|".$value['item_id']."\n", FILE_APPEND|LOCK_EX);
                }else{
                    continue;
                }
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