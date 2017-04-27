<?php
namespace mia\miagroup\Model\Ums;

use Ice;

class Robot extends \DB_Query {

    protected $dbResource = 'miagroupums';
    //头像素材表
    protected $tableAvatarMaterial = 'group_robot_avatar_material';
    //帖子素材表
    protected $tableSubjectMaterial = 'group_robot_subject_materials';
    //编辑帖子表
    protected $tableEditorSubject = 'group_robot_editor_subject';
    //文本素材表
    protected $tableTextMaterial = 'group_robot_text_material';
    
    /**
     * 查询帖子素材表
     */
    public function getSubjectMaterialData($cond, $offset = 0, $limit = 10, $orderBy = '') {
        $this->tableName = $this->tableSubjectMaterial;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'after_id';
                        $where[] = [':gt','id', $v];
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
        $data = $this->getRows($where, 'id', $limit, $offset, $orderBy);
        if (!empty($data)) {
            foreach ($data as $v) {
                $result['list'][] = $v['id'];
            }
        }
        return $result;
    }
    
    /**
     * 查询头像素材表
     */
    public function getAvatarMaterialData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableAvatarMaterial;
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
    
    /**
     * 查询编辑帖子列表
     */
    public function getEditorSubjectData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableEditorSubject;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        $orderBy = $this->tableName. '.id desc';
        $field = "{$this->tableName}.*,{$this->tableSubjectMaterial}.category,{$this->tableSubjectMaterial}.source";
        $join = "left join {$this->tableSubjectMaterial} on {$this->tableName}.material_id = {$this->tableSubjectMaterial}.id ";
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'category':
                    case 'source':
                        $where[] = [$this->tableSubjectMaterial. '.'.$k, $v];
                        break;
                    case 'start_time':
                        $where[] = [':ge',$this->tableName. '.create_time', $v];
                        break;
                    case 'end_time':
                        $where[] = [':le',$this->tableName. '.create_time', $v];
                        break;
                    default:
                        $where[] = [$this->tableName . '.' . $k, $v];
                }
            }
        }
        $result['count'] = $this->count($where, $join, 1);
        if (intval($result['count']) <= 0) {
            return $result;
        }
        $result['list'] = $this->getRows($where, $field, $limit, $offset, $orderBy, $join);
        return $result;
    }
    
    /**
     * 查询文本素材表
     */
    public function getTextMaterialData($cond, $offset = 0, $limit = 50, $orderBy = '') {
        $this->tableName = $this->tableTextMaterial;
        $result = array('count' => 0, 'list' => array());
        $where = array();
        if (!empty($cond)) {
            //组装where条件
            foreach ($cond as $k => $v) {
                switch ($k) {
                    case 'text':
                        $where[] = [':like_literal','text', "%$v%"];
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
        return $result;
    }
    
    /**
     * 查询帖子素材抓取来源
     */
    public function getSubjectMaterialSource() {
        $this->tableName = $this->tableSubjectMaterial;
        $sql = "SELECT DISTINCT(`source`) from {$this->tableName}";
        $data = $this->query($sql);
        if (!empty($data)) {
            foreach ($data as $v) {
                $result[] = $v['source'];
            }
        }
        return $result;
    }

    /**
     * 查询运营帖子编辑人
     */
    public function getEditorSubjectAdmin() {
        $this->tableName = $this->tableEditorSubject;
        $sql = "SELECT DISTINCT(`op_admin`) from {$this->tableName} WHERE op_admin IS NOT NULL and op_admin != ''";
        $data = $this->query($sql);
        if (!empty($data)) {
            foreach ($data as $v) {
                if(empty($v['op_admin'])) {
                    continue;
                }
                $result[] = $v['op_admin'];
            }
        }
        return $result;
    }
    
    /**
     * 查询帖子素材抓取分类
     */
    public function getSubjectMaterialCategory() {
        $this->tableName = $this->tableSubjectMaterial;
        $sql = "SELECT DISTINCT(`category`) from {$this->tableName} WHERE category IS NOT NULL";
        $data = $this->query($sql);
        if (!empty($data)) {
            foreach ($data as $v) {
                if(empty($v['category'])) {
                    continue;
                }
                $result[] = $v['category'];
            }
        }
        return $result;
    }

    /**
     * 删除用户素材
     * @param $id
     * @return array
     */
    public function delAvatarData($id) {
        $this->tableName = $this->tableAvatarMaterial;
        if (empty($id)) {
            return false;
        }
        $where[] = ["id", $id];
        $res = $this->delete($where);
        return $res;
    }

    /**
     * 删除帖子素材
     * @param $id
     * @return array
     */
    public function delMaterial($id)
    {
        $this->tableName = $this->tableSubjectMaterial;
        if (empty($id)) {
            return false;
        }
        $where[] = ["id", $id];
        $res = $this->delete($where);
        return $res;
    }
}