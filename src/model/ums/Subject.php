<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Subject extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //帖子
    protected $tableSubject = 'group_subjects';
    protected $indexSubject = array('id', 'user_id', 'created', 'status', 'is_top', 'is_fine');

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
     * 将口碑id回写到帖子表中的扩展数据中
     */
    public function addKoubeiIdToSubject($koubeiInfo,$subjectId){
        $this->tableName = $this->tableSubject;
        $setData = array();
        $where = array();
        $where[] = ['id', $subjectId];
        $extInfo = array('koubei'=>array(),'image'=>array());
        if(!empty($koubeiInfo['image'])){
            foreach($koubeiInfo['image'] as $image){
                $url = parse_url($image['url']);
                $extInfo['image']['url'] = ltrim($url['path'],'/');
                $extInfo['image']['width'] = $image['width'];
                $extInfo['image']['height'] = $image['height'];
            }
        }
        $extInfo['koubei']['id'] = $koubeiInfo['id'];
        
        $extInfo = json_encode($extInfo);
        $setData[] = ['ext_info',$extInfo];
        
        $result = $this->update($setData,$where);
        return $result;
    }
    

}