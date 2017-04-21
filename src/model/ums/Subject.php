<?php
namespace mia\miagroup\Model\Ums;
use mia\miagroup\Data\Subject\TabNoteOperation;
use mia\miagroup\Data\Subject\Tab as TabData;
use Ice;

class Subject extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //帖子
    protected $tableSubject = 'group_subjects';
    protected $indexSubject = array('id', 'user_id', 'created', 'status', 'is_top', 'is_fine');
    //帖子草稿
    protected $tableSubjectDraft = 'group_subject_draft';

    protected $tabData = null;
    protected $tabOpeationData = null;
    
    public function __construct() {
        $this->tabData = new TabData();
        $this->tabOpeationData = new TabNoteOperation();
    }
    /**
     * 查询口碑表数据
     */
    public function getSubjectData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableSubject;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //检查是否使用索引，没有索引强制加
            if (empty(array_intersect(array_keys($cond), $this->indexSubject))) {
                $where[] = [':ge','created', date('Y-m-d H:i:s', time() - 86400 * 90)];
            }
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'title':
                        $where[] = [':like_literal','title', "%$v%"];
                        break;
                    case 'start_time':
                        $where[] = [':ge','created', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le','created', $v];
                        break;
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        if (!empty($result['list'])) {
            foreach ($result['list'] as $k => $v) {
                $result['list'][$k]['subject_id'] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 获取首页，推荐栏目，运营数据
     * @param $tabId
     * @param $page
     * @return array
     */
    public function getOperationNoteData($tabId, $page, $timeTag=null)
    {
        if (empty($tabId)) {
            return [];
        }
        $conditions['tab_id'] = $tabId;
        $conditions['page'] = $page;
        if(isset($timeTag)){
            $conditions['time_tag'] = $timeTag;
        }
        $result = array();
        $operationInfos = $this->tabOpeationData->getBatchOperationNotes($conditions);
        if(!empty($operationInfos)){
            $result = $operationInfos;
        }
        return $result;
    }
    
    /**
     * 获取帖子草稿列表
     */
    public function getSubjectDraftList($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableSubjectDraft;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    default:
                        $where[] = [$k, $v];
                }
            }
        }
        $result['count'] = $this->count($where);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, '*', $limit, $offset, $orderBy);
        return $result;
    }
}