<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 帖子内容语义分析
 */
class Contentanalys extends \FD_Daemon {

    private $lastIdFile;
    private $python_bin;
    private $negative_path;

    public function __construct() {
        ini_set('display_errors', 'On');
        error_reporting('E_ALL');
        $this->python_bin = \F_Ice::$ins->workApp->config->get('app.daemon_python_bin');
        $this->negative_path = '/home/work/negative_notes/bin/';
        //加载定时脚本临时文件存放地址
        $runFilePath = \F_Ice::$ins->workApp->config->get('app.run_path');
        $tempFilePath = $runFilePath . '/subject/';
        $this->lastIdFile = $tempFilePath . 'content_analys_last_id';
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
        
        //拉取新发布的帖子
        $subjectData = new \mia\miagroup\Data\Subject\Subject();
        $where = [];
        $where[] = [':gt','id', $lastId];
        $where[] = ['source', [1, 2]];
        $where[] = ['status', 1];
        $data = $subjectData->getRows($where, 'id, title, text', 10);
        if (empty($data)) {
            return ;
        }
        foreach ($data as $value) {
            if (isset($maxId)) { //获取最大event_id
                $maxId = $value['id'] > $maxId ? $value['id'] : $maxId;
            } else {
                $maxId = $value['id'];
            }
            //标题、文本为空直接过
            if (empty($value['title']) && empty($value['text'])) {
                continue;
            }
            //好评差评识别
            $analys_content = "{$value['title']}{$value['text']}";
            $analys_content = str_replace("\r\n", ' ', $analys_content);
            $analys_content = str_replace("\n", ' ', $analys_content);
            $analys_content = str_replace("'", '"', $analys_content);
            $cmd = "{$this->python_bin} {$this->negative_path}wordseg_client_notes.py {$this->negative_path}new_model_3 '{$analys_content}'  {$this->negative_path}new_top_a_good";
            $analys_result = system($cmd);
            switch ($analys_result) {
                case 1:
                    $analys_result = 3;
                    break;
                case 2:
                    $analys_result = 2;
                    break;
                case 0:
                    $analys_result = 1;
                    break;
            }
            //更新文本分析结果
            $setData[] = array('semantic_analys', $analys_result);
            $subjectData->updateSubject($setData, $value['id']);
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