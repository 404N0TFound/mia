<?php
namespace mia\miagroup\Daemon\Subject;

/**
 * 帖子草稿相关脚本
 */
class Subjectdraft extends \FD_Daemon {
    
    private $subjectDraftData;
    private $subjectService;
    private $subjectUmsModel;
    
    public function __construct(){
        $this->subjectDraftData = new \mia\miagroup\Data\Subject\SubjectDraft();
        $this->subjectService = new \mia\miagroup\Service\Subject();
        $this->subjectUmsModel = new \mia\miagroup\Model\Ums\Subject();
    }
    
    public function execute() {
        $function_name = $this->request->argv[0];
        $this->$function_name();
    }
    
    private function subject_clocking_pub() {
        $cond['status'] = 1;
        $cond['end_publish_time'] = date('Y-m-d H:i:s');
        $data = $this->subjectUmsModel->getSubjectDraftList($cond, 0, 1000);
        $data = $data['list'];
        if (empty($data)) {
            return ;
        }
        foreach ($data as $v) {
            $v['issue_info']['subject_info']['created'] = $v['publish_time'];
            $result = $this->subjectService->issue($v['issue_info']['subject_info'], $v['issue_info']['point_tags'], $v['issue_info']['labels']);
            if ($result['code'] > 0) {
                continue;
            }
            $update_data['status'] = 2;
            $update_data['subject_id'] = $result['data']['id'];
            $this->subjectDraftData->updateDraftById($v['id'], $update_data);
        }
    }
}