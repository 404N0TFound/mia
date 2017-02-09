<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 导出帖子数据
 */
class Subjectsync extends \FD_Daemon {
    
    private $mode;
    private $remote;
    private $tempFilePath;
    
    public function __construct() {
        $this->remote = \F_Ice::$ins->workApp->config->get('thrift.address.subject_sync');
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
                $folderPath = $this->tempFilePath . $folderName . '/';
                $cmd = 'rsync -avz --exclude="' . $folderName . '" ' . $folderName . '* ' . $this->remote ;
                exec($cmd);
                break;
        }
    
    }

    private function fullDataSync($date) {
        $filePath = $this->tempFilePath . $date;
        //生成done文件
        file_put_contents($filePath . '/done', date('Y-m-d H:i:s'));
        //推送全量文件到目标机器
        $cmd = "rsync -avz $filePath $this->remote";
        exec($cmd);
        //删除历史数据
        $cmd = "rm {$filePath}* -rf";
        exec($cmd);
    }
}