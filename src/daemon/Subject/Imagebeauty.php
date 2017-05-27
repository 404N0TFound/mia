<?php
namespace mia\miagroup\Daemon\Subject;

use mia\miagroup\Service\Image as ImageService;
use mia\miagroup\Data\Subject\Subject as SubjectData;

/**
 * 帖子图片美化
 */
class Imagebeauty extends \FD_Daemon
{
    private $mode;
    private $lastIdFile;
    private $subjectData;
    private $tempFilePath;
    private $image_service;
    private $subjectImageSuccessData;
    private $dumpErrorSubjectImageFile;
    private $dumpUpdateErrorSubjectImageFile;

    public function __construct()
    {
        $this->subjectData = new SubjectData();
        $this->image_service = new ImageService();
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/subject/';
    }

    public function execute()
    {
        // 切换模式
        $this->mode = $this->request->argv[0];
        if (empty($this->mode)) {
            return ;
        }
        $imageCoreDir =  $this->tempFilePath.'image/';
        $this->mk_dir($imageCoreDir);
        $folderName = date('Ymd');
        $folderPath = $imageCoreDir . $folderName . '/';
        $this->mk_dir($folderPath);
        $this->subjectImageSuccessData = $folderPath . 'success_subject_image_id';
        switch ($this->mode) {
            case 'full_dump':
                $this->lastIdFile = $imageCoreDir . 'full_image_dump_last_id';
                $this->dumpErrorSubjectImageFile = $imageCoreDir . 'full_dump_error_subject_image_id';
                $this->beautySubjectImage();
                //更新失败处理帖子
                $this->dumpUpdateErrorSubjectImageFile = $imageCoreDir . 'full_dump_update_error_image';
                $res = $this->updateErrorSubImageIds();
                if(!empty($res)) {
                    unlink($this->dumpErrorSubjectImageFile);
                }
                break;
            case 'incremental_dump':
                $this->lastIdFile = $imageCoreDir . 'incr_image_dump_delta_id';
                $this->dumpErrorSubjectImageFile = $imageCoreDir . 'incr_dump_error_subject_image_id';
                $this->beautySubjectImage();
                //更新失败处理帖子
                $this->dumpUpdateErrorSubjectImageFile = $imageCoreDir . 'incr_dump_update_error_image';
                $res = $this->updateErrorSubImageIds();
                if(!empty($res)) {
                    unlink($this->dumpErrorSubjectImageFile);
                }
                break;
        }
    }


    public function beautySubjectImage()
    {
        @ini_set('memory_limit', '512M');
        ini_set('gd.jpeg_ignore_warning', 1);
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

        // 初始化
        if($lastId <= 0) {
            $subjectData = new \mia\miagroup\Data\Subject\Subject();
            $initId = $subjectData->getRow(array(), 'max(id) as maxid');
            $initId = intval($initId['maxid']);
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $initId);
            flock($fpLastIdFile, LOCK_UN);
            fclose($fpLastIdFile);
            return ;
        }

        $orderBy = FALSE;
        $where = [];
        $where[] = ['status', 1];
        if($this->mode == 'full_dump') {
            $where[] = [':lt','id', $lastId];
            $orderBy = 'id desc';
        }else {
            $where[] = [':gt','id', $lastId];
        }
        $where[] = [':ne','ext_info', ''];
        $where[] = [':ne','image_url', ''];

        $limit = 500;
        $field = 'id, ext_info';
        $data = $this->subjectData->getRows($where, $field, $limit, 0, $orderBy);
        if (empty($data)) {
            return ;
        }

        // 图片处理
        $maxId = $this->handle_image($data);

        //写入本次处理的最大event_id
        if (isset($maxId)) {
            fseek($fpLastIdFile, 0, SEEK_SET);
            ftruncate($fpLastIdFile, 0);
            fwrite($fpLastIdFile, $maxId);
        }
        flock($fpLastIdFile, LOCK_UN);
        fclose($fpLastIdFile);
    }

    /*
     * 美化图片处理
     * */
    public function handle_image($data, $conditions = array())
    {
        $fp = fopen($this->dumpErrorSubjectImageFile, 'a');
        $successFp = fopen($this->subjectImageSuccessData, 'a');
        if (!empty($data)) {
            foreach ($data as $value) {
                $where = [];
                $subject_id = $value['id'];

                if($this->mode == 'full_dump') {
                    // 全量
                    if (isset($logId)) { //获取最大event_id
                        $logId = $subject_id < $logId ? $subject_id : $logId;
                    } else {
                        $logId = $subject_id;
                    }
                } else {
                    // 增量
                    if (isset($logId)) { //获取最大event_id
                        $logId = $subject_id > $logId ? $subject_id : $logId;
                    } else {
                        $logId = $subject_id;
                    }
                }

                $beauty = array();
                $beauty_image = array();

                // 不存在
                if (empty($value['ext_info'])) {
                    continue;
                }
                $ext_info = json_decode($value['ext_info'], true);

                // 已经美化
                if (!empty($ext_info['beauty_image'])) {
                    continue;
                }
                $ori_image_list = $ext_info['image'];

                if(empty($ori_image_list)) {
                    continue;
                }
                foreach ($ori_image_list as $image) {
                    if(empty($image['url'])) {
                        // 后续不处理
                        $beauty_image = array();
                        break;
                    }
                    $image_url = $this->image_service->beautyImage($image['url'])['data'];
                    if (empty($image_url)) {
                        $beauty_image = array();
                        break;
                    }
                    $beauty['url'] = $image_url;
                    $beauty['width'] = $image['width'];
                    $beauty['height'] = $image['height'];
                    $beauty_image[] = $beauty;
                }
                if(empty($beauty_image)) {
                    // 记录失败帖子ID
                    if($conditions['action'] != 'update') {
                        fwrite($fp, $subject_id."\n");
                    }
                    continue;
                }
                $ext_info['beauty_image'] = $beauty_image;
                // 更新数据库
                $where[] = ['id', $subject_id];
                $setData[] = ['ext_info', json_encode($ext_info)];
                $res = $this->subjectData->update($setData, $where);
                if(!empty($res)) {
                    fwrite($successFp, $subject_id."\n");
                }
            }
        }
        return $logId;
    }

    /*
     * 更新美化失败帖子
     * */
    public function updateErrorSubImageIds()
    {
        if(empty(filesize($this->dumpErrorSubjectImageFile))){
            return false;
        }
        if (!file_exists($this->dumpUpdateErrorSubjectImageFile)) {
            $updateFile = fopen($this->dumpUpdateErrorSubjectImageFile, 'w');
        } else {
            $updateFile = fopen($this->dumpUpdateErrorSubjectImageFile, 'a');
        }

        $handle = @fopen($this->dumpErrorSubjectImageFile, "r");

        if ($handle) {
            while (!feof($handle)) {
                $subject_id = trim(fgets($handle, 2048));
                if(empty($subject_id)) {
                    continue;
                }
                $where = [];
                $where[] = ['status', 1];
                $where[] = [':eq','id', $subject_id];
                $where[] = [':ne','ext_info', ''];
                $where[] = [':ne','image_url', ''];

                $field = 'id, ext_info';
                $data = $this->subjectData->getRows($where, $field);
                if(empty($data)) {
                    continue;
                }
                $handleId = $this->handle_image($data, array('action' => 'update'));
                // 更新成功id记录日志
                fwrite($updateFile, $handleId."\n");
            }
        }
        return true;
    }


    /**
     * 检测路径是否存在并自动生成不存在的文件夹
     */
    private function mk_dir($path) {
        if(is_dir($path)) return true;
        if(empty($path)) return false;
        $path = rtrim($path, '/');
        $bpath = dirname($path);
        if(!is_dir($bpath)) {
            if(!$this->mk_dir($bpath)) return false;
        }
        if(!@chdir($bpath)) return false;
        if(!@mkdir(basename($path))) return false;
        return true;
    }
}