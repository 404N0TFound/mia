<?php
namespace mia\miagroup\Daemon\Temp;

/**
 * 标签相关临时脚本
 */
class Label extends \FD_Daemon
{
    private $labelData;
    
    public function __construct() {
        $this->labelData = new \mia\miagroup\Data\Label\SubjectLabelRelation();
    }

    public function execute()
    {
        $file_path = '/home/hanxiang/label_copy';
        $label_ids = file($file_path);
        $i = 0;
        foreach ($label_ids as $label_id) {
            $label_id = trim($label_id);
            list($ori_id, $new_id) = explode("\t", $label_id);
            var_dump($ori_id, $new_id, $label_id);exit;
            $where = [];
            $where[] = ['label_id',$label_id];
            $data = $this->labelData->getRows($where);
            foreach ($data as $v) {
                unset($v['id']);
                $v['label_id'] = $new_id;
                $this->labelData->insert($v);
                $i ++;
                if ($i % 1000 == 0) {
                    sleep(1);
                }
            }
        }
    }
}