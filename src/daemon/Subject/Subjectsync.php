<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 导出帖子数据
 */
class Subjectsync extends \FD_Daemon {
    
    private $mode;
    private $remote;
    private $recommend_14;
    private $tempFilePath;
    
    public function __construct() {
        $this->remote = \F_Ice::$ins->workApp->config->get('thrift.address.subject_sync');
        $this->recommend_14 = \F_Ice::$ins->workApp->config->get('thrift.address.recommend_14');
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $this->tempFilePath = $runFilePath . '/subject/';
    }
    
    public function execute() {
        
        $mode = $this->request->argv[0];
        if (empty($mode)) {
            return ;
        }
        $this->mode = $mode;
        switch ($this->mode) {
            case 'full_dump':
                $date = $this->request->argv[1];
                $date = $date ? $date : date('Ymd', strtotime('-1 day'));
                $this->fullDataSync($date);
                break;
            case 'incremental_dump':
                $folderName = date('Ymd');
                $cmd = 'rsync -avz --exclude="' . $folderName . '" ' . $this->tempFilePath . $folderName . '* ' . $this->remote ;
                exec($cmd);
                break;
        }
    
    }

    private function fullDataSync($date) {
        $filePath = $this->tempFilePath . $date;
        //生成done文件
        file_put_contents($filePath . '/done', date('Y-m-d H:i:s'));
        //推送全量文件到目标机器
        $cmd = "scp -r $filePath {$this->remote}do_not_delete";
        exec($cmd);
        $cmd = "ssh -t  root@{$this->recommend_14} mv /opt/article_in_mia/do_not_delete /opt/article_in_mia/$date";
        //删除历史数据
        $cmd = "rm {$filePath}* -rf";
        exec($cmd);
    }
}